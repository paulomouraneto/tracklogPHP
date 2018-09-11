<?php
/**
* Class that represents a GPX Tracklog file.
*
*@author Alex Sandro de Araujo Souza - @asasouza
*@version 1.0 2017/12/05
*/
class KML extends Tracklog{

	/**
	*Constructs the object based on a KML file and populates the $trackData array.
	*
	*@param $file The path of the file to be parsed.
	*
	*@return A KML object.
	*/
	public function __construct($file){
		try {
			$this->validate($file);
			$xml = simplexml_load_file($file);
			$xml->registerXPathNamespace('kml', $xml->getNamespaces()[""]);
			$xml->registerXPathNamespace('gx', 'http://www.google.com/kml/ext/2.2');
			if (!empty($content = $xml->xpath('//kml:LineString')) && strlen($content[0]->coordinates) > 0) {
				foreach ($content as $trackSegment) {
					$trackData = array();
					foreach ($trackSegment->coordinates as $linestring) {
						$linestring = preg_split('/\s+/', trim($linestring));
						foreach ($linestring as $linestring) {
							$pointData = explode(',', $linestring);	
							$trackPoint = new TrackPoint();
							$trackPoint->setLongitude($pointData[0]);
							$trackPoint->setLatitude($pointData[1]);
							isset($pointData[2]) ? $trackPoint->setElevation($pointData[2]) : 0;
							array_push($trackData, $trackPoint);
						}					
					}
					array_push($this->trackData, $trackData);
				}
			}elseif(!empty($content = $xml->xpath('//gx:Track'))  ){
				foreach ($content as $trackSegment) {
					$trackData = array();
					if (!empty($times = $xml->xpath('//gx:Track/kml:when')) && !empty($points = $xml->xpath('//gx:Track/gx:coord')) && count($times) == count($points)) {
						foreach ($points as $i => $pointData) {
							$pointData = explode(' ', $pointData);
							$trackPoint = new TrackPoint();
							$trackPoint->setLongitude($pointData[0]);
							$trackPoint->setLatitude($pointData[1]);
							isset($pointData[2]) ? $trackPoint->setElevation($pointData[2]) : 0; //verify if exists elevation data.
							$trackPoint->setTime($times[$i]);
							array_push($trackData, $trackPoint);
						}
					}else{
						throw new TracklogPhpException("This file doesn't appear to have any tracklog data.");
					}
					array_push($this->trackData, $trackData);
				}
			}else{
				throw new TracklogPhpException("This file doesn't appear to have any tracklog data.");
			}

			$this->populateDistance();

			isset($xml->xpath('//kml:Document/kml:name')[0]) ? $this->trackName = $xml->xpath('//kml:Document/kml:name')[0] : 0;

			/** Get the markers of the track */
			if (!empty($markers = $xml->xpath('//kml:Placemark'))) {
				foreach ($markers as $marker) {
					if ($marker->Point) {
						$pointData = explode(',', $marker->Point->coordinates);	
						$trackPoint = new TrackPoint();
						$trackPoint->setLongitude($pointData[0]);
						$trackPoint->setLatitude($pointData[1]);
						isset($pointData[2]) ? $trackPoint->setElevation($pointData[2]) : 0;
						isset($marker->name) ? $trackPoint->setName($marker->name) : 0;
						array_push($this->trackMarkers, $trackPoint);
					}
				}
			}

			return $this;
		} catch (TracklogPhpException $e) {
			throw $e;
		}
	}

	/**
	*Write the XML of a KML file based on the $trackData array.
	*
	*@param $file_path (optional) Path to save the created file.
	*
	*@return Returns a string containing the content of the created file.
	*/
	protected function write($file_path = null){
		$kml = new SimpleXMLElement('<kml/>');	
		$kml->addAttribute('xmlns','http://www.opengis.net/kml/2.2');
		$kml->addAttribute('xmlns:xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$kml->addAttribute('xmlns:xmlns:gx','http://www.google.com/kml/ext/2.2');
		$kml->addAttribute('xsi:xsi:schemaLocation','http://www.opengis.net/kml/2.2 http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd http://www.google.com/kml/ext/2.2 http://developers.google.com/kml/schema/kml22gx.xsd">');
		$document = $kml->addChild('Document');
		if ($this->hasTime()) {
			$folder = $document->addChild('Folder');
			if (isset($this->trackName)) {
				$folder->addChild('name', $this->trackName);
			}
			$folder->addChild('open', 1);
			$placemark = $folder->addChild('Placemark');
			foreach ($this->trackData as $trackSegment) {
				$gxtrack = $placemark->addChild('gx:gx:Track');
				foreach ($trackSegment as $trackPoint) {
					$gxtrack->addChild('when', $trackPoint->getTime());
				}
				foreach ($trackSegment as $trackPoint) {
					$coordinates = $trackPoint->getLongitude().' '.$trackPoint->getLatitude();
					$coordinates .= $this->hasElevation() ? ' '.$trackPoint->getElevation() : "";
					$gxtrack->addChild('gx:gx:coord', $coordinates);
				}
			}			
		}else{
			$folder = $document->addChild('Folder');
			if (isset($this->trackName)) {
				$folder->addChild('name', $this->trackName);
			}
			$placemark = $folder->addChild('Placemark');
			if (isset($this->trackName)) {
				$placemark->addChild('name', $this->trackName);
			}
			$placemark->addChild('visibility', 1);
			$placemark->addChild('open', 1);
			foreach ($this->trackData as $trackSegment) {
				$linestring = $placemark->addChild('LineString');
				$linestring->addChild('extrude', 'true');
				$linestring->addChild('tessellate', 'true');						
				$trackData = '';
				foreach ($trackSegment as $trackPoint) {
					$trackData .= $trackPoint->getLongitude().','.$trackPoint->getLatitude();
					$trackData .= $this->hasElevation() ? ',' . $trackPoint->getElevation().'&#10;' : '&#10;';
				}
				$coordinates = $linestring->addChild('coordinates', $trackData);	
			}
		}

		if (!empty($this->trackMarkers)) {
			$folder = $document->addChild('Folder');
			$folder->addChild('name', 'Waypoints');
			$folder->addChild('open', 'true');
			foreach ($this->trackMarkers as $marker) {
				$placemark = $folder->addChild('Placemark');
				$placemark->addChild('Snippet')->addAttribute('maxLines', '0');
				$placemark->addChild('name', $marker->getName());
				$placemark->addChild('description', $marker->getName() . ' Teste');
				$placemark->addChild('styleUrl', '');
					$point = $placemark->addChild('Point');
					$coordinate = $marker->getLongitude().','.$marker->getLatitude();
					$coordinate .= $this->hasElevation() ? ','.$marker->getElevation() : '';
					$point->addChild('coordinates', $coordinate);
			}
		}

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom_xml = dom_import_simplexml($kml);
		$dom_xml = $dom->importNode($dom_xml, true);
		$dom_xml = $dom->appendChild($dom_xml);
		if (!is_null($file_path)) {
			$dom->save($file_path.".kml");
		}
		return $dom->saveXML();
	}

	/** Validates a KML file based on the oficial XSD schema of the format and the extension GX from Google. */
	protected function validate($file){
		set_error_handler(array('Tracklog', 'error_handler'));
		$dom = new DOMDocument;
		if (!file_exists($file)) {
			throw new Exception('Failed to load external entity "' . $file . '"');
		}else{
			$dom->load($file);	
		}		
		try {			
			$dom->schemaValidate("lib/formats/xsd_files/". get_class($this) .".xsd");
		} catch (TracklogPhpException $e) {
			$e->setMessage("This isn't a valid " . get_class($this) . " file.");
			throw $e;
		}
		restore_error_handler();
	}
}
?>