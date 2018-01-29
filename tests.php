<?php
require_once('lib/tracklogPhp.main.php');

$extensions = ['kml', 'tcx', 'gpx', 'csv', 'js'];
$files = scandir("test_files/external_files_diversos");
sort($files, SORT_NUMERIC);
$array = array();
foreach ($files as $key => $file) {
	$extension = pathinfo($file, PATHINFO_EXTENSION);
	if (in_array($extension, $extensions)) {
		$file_path = "test_files/external_files_diversos/".$file;
		try {
			($extension == "js") ? $extension = "geojson" : 0;
			echo "Inicio $file <br>";
			$time_start = microtime(true);
			$tracklog = new $extension($file_path);
			
			//Metodos não tabulados mas utilizado no controller
			$tracklog->getTotalTime();
			$tracklog->getPace();
			$tracklog->getPaces("seconds", true);
			$tracklog->getAverageSpeeds();
			$tracklog->getElevations();
			$tracklog->getTrackName();
			//fim

			$array[$key]["Nome"] = $file;
			$array[$key]["Distance"] = $tracklog->getTotalDistance("kilometers");

			try {
				$array[$key]["Elevacao"] = number_format($tracklog->getElevationGain(), 2, ",","") . " / " . number_format($tracklog->getElevationLoss(), 2, ",", "");
				$array[$key]["Elevacao Máxima"] = number_format($tracklog->getMaxElevation(), 2, ",", "");
			} catch (Exception $e) {
				$array[$key]["Elevation"] = " - ";
				$array[$key]["Elevação Máxima"] = " - ";
			}

			try {
				$array[$key]["Pace"] = $tracklog->getPace();
				$array[$key]["Velocidade"] = $tracklog->getAverageSpeed();	
			} catch (Exception $e) {
				$array[$key]["Pace"] = " - ";
				$array[$key]["Velocidade"] = " - ";
			}
			
			$array[$key]["Pontos de GPS"] = count($tracklog->getPoints());
			$array[$key]["Tempo Execução"] = number_format((microtime(true) - $time_start), 10, ",", "");

			$time_start = microtime(true);
			$tracklog->out("TCX");
			$array[$key]["ConversaoGPX"] = number_format((microtime(true) - $time_start), 10, ",", "");

			$time_start = microtime(true);
			$tracklog->out("GPX");
			$array[$key]["ConversaoGPX"] = number_format((microtime(true) - $time_start), 10, ",", "");

			$time_start = microtime(true);
			$tracklog->out("KML");
			$array[$key]["ConversaoGPX"] = number_format((microtime(true) - $time_start), 10, ",", "");

			$time_start = microtime(true);
			$tracklog->out("CSV");
			$array[$key]["ConversaoGPX"] = number_format((microtime(true) - $time_start), 10, ",", "");

			$time_start = microtime(true);
			$tracklog->out("GeoJSON");
			$array[$key]["ConversaoGPX"] = number_format((microtime(true) - $time_start), 10, ",", "");

		}catch(Exception $e){
			$array[$key]["Error"] = $e->getMessage();
		}		
		echo "Fim $file <br>";
	}
}

$file = fopen("test_files/results.csv", 'w');
fputcsv($file, ["Nome", "Total Distance", "ElevationGain/ElevationLoss", "Elevacao Maxima", "Pace", "Velocidade", "Ponto de GPS", "Tempo de Execucao", "Tempo de Conversao TCX","Tempo de Conversao GPX","Tempo de Conversao KML","Tempo de Conversao CSV","Tempo de Conversao GeoJSON", "Error"], ";");
foreach ($array as $key => $value) {
	fputcsv($file, $value, ";");
}
fclose($file);

?>