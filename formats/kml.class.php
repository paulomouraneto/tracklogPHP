<?php
class KML extends Tracklog{

	public function __construct($file){
		try {
			parent::validate($file);
			$xml = simplexml_load_file($file);
			$xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
			$xml->registerXPathNamespace('gx', 'http://www.google.com/kml/ext/2.2');

			if (!empty($content = $xml->xpath('//kml:coordinates'))) {
				$content = preg_replace('/\s+/', ',', $content);
				$coordinates = explode(',', $content[0]);
				$y = 0;
				for ($i=0; $i < count($coordinates);) {			
					$this->trackData[$y]['lon'] = number_format((float) $coordinates[$i], 7);
					$this->trackData[$y]['lat'] = number_format((float) $coordinates[$i+1], 7);
					$this->trackData[$y]['ele'] = number_format((float) $coordinates[$i+2], 6);
					$i = $i+3;
					$y++;
				}
			}elseif(!empty($times = $xml->xpath('//gx:Track/kml:when')) && !empty($coordinates = $xml->xpath('//gx:Track/gx:coord'))){
				foreach ($coordinates as $i => $coordinate) {
					$coordinate = explode(' ', $coordinate);
					$this->trackData[$i]['lon'] = number_format((float) $coordinate[0], 7);
					$this->trackData[$i]['lat'] = number_format((float) $coordinate[1], 7);
					$this->trackData[$i]['ele'] = number_format((float) $coordinate[2], 6);
					$this->trackData[$i]['time'] = (string) $times[$i];
				}
			}else{
				throw new TracklogPhpException("This file doesn't appear to have any tracklog data.");
			}

			$this->populateDistance();
			return $this;

		} catch (TracklogPhpException $e) {
			throw $e;
		}
	}

	public function getTime(){
		if ($this->hasTime()) {
			return parent::getTime();
		}else{
			throw new TracklogPhpException("This KML file don't support time manipulations");	
		}
	}

	public function getPace(){
		if ($this->hasTime()) {
			return parent::getPace();
		}else{
			throw new TracklogPhpException("This KML file don't support time manipulations");	
		}
	}

	public function getTotalTime($format = null){
		if ($this->hasTime()) {
			return parent::getTotalTime($format);
		}else{
			throw new TracklogPhpException("This KML file don't support time manipulations");	
		}
	}

	protected function write($file_path = null){
		$kml = new SimpleXMLElement('<kml/>');	
		$kml->addAttribute('xmlns','http://www.opengis.net/kml/2.2');
		$kml->addAttribute('xmlns:xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$kml->addAttribute('xmlns:xmlns:gx','http://www.google.com/kml/ext/2.2');
		$kml->addAttribute('xsi:xsi:schemaLocation','http://www.opengis.net/kml/2.2 http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd http://www.google.com/kml/ext/2.2 http://developers.google.com/kml/schema/kml22gx.xsd">');
			$document = $kml->addChild('Document');
				if ($this->hasTime()) {
				$folder = $document->addChild('Folder');
					if (isset($this->trackData['meta_tag']['name'])) {
					$folder->addChild('name', $this->trackData['meta_tag']['name']);
					}
					$folder->addChild('open', 1);
						$placemark = $folder->addChild('Placemark');
							$gxtrack = $placemark->addChild('gx:gx:Track');
								foreach ($this->trackData as $time) {
									$gxtrack->addChild('when', $time['time']);
								}
								foreach ($this->trackData as $coordinate) {
									$gxtrack->addChild('gx:gx:coord', $coordinate['lon'].' '.$coordinate['lat'].' '.$coordinate['ele']);
								}
				}else{
				$placemark = $document->addChild('Placemark');
					if (isset($this->trackData['meta_tag']['name'])) {
						$placemark->addChild('name', $this->trackData['meta_tag']['name']);
					}
					$placemark->addChild('visibility', 1);
					$placemark->addChild('open', 1);
					$linestring = $placemark->addChild('LineString');
						$linestring->addChild('extrude', 'true');
						$linestring->addChild('tessellate', 'true');						
						$trackData = '';
						foreach ($this->trackData as $coordinates) {
							$trackData = $trackData . $coordinates['lon'].','.$coordinates['lat'].','.$coordinates['ele']. '&#10;';
						}
						$coordinates = $linestring->addChild('coordinates', $trackData);							
				}

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom_xml = dom_import_simplexml($kml);
		$dom_xml = $dom->importNode($dom_xml, true);
		$dom_xml = $dom->appendChild($dom_xml);
		if (!is_null($file_path)) {
			$dom->save($file_path);
		}
		return $dom->saveXML();
	}

	protected function validate($file){
		set_error_handler(array('Tracklog', 'error_handler'));
		$dom = new DOMDocument;
		if (!file_exists($file)) {
			throw new Exception('Failed to load external entity "' . $file . '"');
		}else{
			$dom->load($file);	
		}		
		try {			
			$dom->schemaValidate("xsd_files/". get_class($this) .".xsd");
		} catch (Exception $e) {
			throw new TracklogPhpException("This isn't a valid " . get_class($this) . " file.");
		}	
	}
}
?>