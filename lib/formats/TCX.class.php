<?php 

class TCX extends Tracklog{
	
	public function __construct($file){
		try {
			$this->validate($file);
			$xml = simplexml_load_file($file);
			$xml->registerXPathNamespace('tcx', 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');
			if (!empty($content = $xml->xpath('//tcx:Track/tcx:Trackpoint'))) {
				foreach ($content as $pointData) {
					$trackPoint = new TrackPoint();
					$trackPoint->setLatitude($pointData->Position->LatitudeDegrees);
					$trackPoint->setLongitude($pointData->Position->LongitudeDegrees);
					$trackPoint->setTime($pointData->Time);
					!empty($pointData->AltitudeMeters) ? $trackPoint->setElevation($pointData->AltitudeMeters) : 0;
					!empty($pointData->DistanceMeters) ? $trackPoint->setDistance($pointData->DistanceMeters) : 0;
					array_push($this->trackData, $trackPoint);
				}
				!$this->hasDistance() ? $this->populateDistance(): 0;
				$this->trackName = $xml->xpath('//tcx:Course/tcx:Name')[0];
				return $this;
			}else{
				throw new TracklogPhpException("This file doesn't appear to have any tracklog data.");
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	protected function write($file_path = null){
		$tcx = new SimpleXMLElement('<TrainingCenterDatabase/>');
		$tcx->addAttribute('xmlns', 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');
		$tcx->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$tcx->addAttribute('xsi:xsi:schemaLocation', 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd');
		$courses = $tcx->addChild('Courses');
			$course = $courses->addChild('Course');
				$course->addChild('Name', isset($this->trackName) ? $this->trackName : 'TrackLogPHPConv');
				$lap = $course->addChild('Lap');
					$lap->addChild('TotalTimeSeconds', ($this->hasTime()) ? $this->getTotalTime('seconds') : 0.0);
						$lap->addChild('DistanceMeters', $this->getTotalDistance('meters'));
						$begginPosition = $lap->addChild('BeginPosition');
							$begginPosition->addChild('LatitudeDegrees', $this->trackData[0]->getLatitude());
							$begginPosition->addChild('LongitudeDegrees', $this->trackData[0]->getLongitude());
						$endPosition = $lap->addChild('EndPosition');
							$endPosition->addChild('LatitudeDegrees', $this->trackData[count($this->trackData)-1]->getLatitude());
							$endPosition->addChild('LongitudeDegrees', $this->trackData[count($this->trackData)-1]->getLongitude());
						$lap->addChild('Intensity', 'Active');
				$track = $course->addChild('Track');
				foreach ($this->trackData as $trackPoint) {
					$trackpoint = $track->addChild('Trackpoint');
						$this->hasTime() ? $trackpoint->addChild('Time', $trackPoint->getTime()) : $trackpoint->addChild('Time', date('Y-m-d\T00:00:00\Z'));
						$position = $trackpoint->addChild('Position');
							$position->addChild('LatitudeDegrees', $trackPoint->getLatitude());
							$position->addChild('LongitudeDegrees', $trackPoint->getLongitude());
						$this->hasElevation() ? $trackpoint->addChild('AltitudeMeters', $trackPoint->getElevation()) : 0;
						$trackpoint->addChild('DistanceMeters', $trackPoint->getDistance());
				}
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom_xml = dom_import_simplexml($tcx);
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
			$dom->schemaValidate("lib/formats/xsd_files/". get_class($this) .".xsd");
		} catch (TracklogPhpException $e) {
			$e->setMessage("This isn't a valid " . get_class($this) . " file.");
			throw $e;
		}
		restore_error_handler();
	}
}
?>