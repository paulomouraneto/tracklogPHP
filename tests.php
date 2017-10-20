<?php
require_once('lib/tracklogPhp.main.php');


// $file = 'test_files/external_files/test_322/322km';
// $file = "test_files/external_files_avbox/errado - trekking-morro-do-bau-ilhota-sc1-morro-do-bau";
// $file = "test_files/external_files_avbox/certo - travessia-toca-do-lopo-a-casa-de-pedra-travessia-toca-do-lopo-a-casa-de-pedra";
$file = "test_files/external_files_avbox/errado - elevacao - cachoeira-do-gato-e-praia-do-gato-ilhabela-tracklog-cachoeira-do-gato";

$kml = new KML($file.'.kml');
// $gpx = new GPX($file.'.gpx');
// $csv = new CSV($file.'.csv');
// $tcx = new TCX($file.'.tcx');
// $json = new GeoJson($file.'.js');
// $json2 = new GeoJson($file.'.2.js');

echo count($kml->getPoints('kilometers')) . "<br>";
echo $kml->getElevationGain() . "<br>";
echo $kml->getElevationLoss() . "<br>";

// echo count($gpx->getPoints('kilometers')) . "<br>";
// echo count($csv->getPoints('kilometers')) . "<br>";
// echo count($tcx->getPoints('kilometers')) . "<br>";
// echo count($json->getPoints('kilometers')) . "<br>";
// echo count($json2->getPoints('kilometers')) . "<br>";

// print_r($gpx->getPaces("seconds"));
// print_r($gpx->getTimes());


?>