<?php

date_default_timezone_set('UTC');

$run=$argv[1];
$sid=$argv[2];

$stations=array(
  '07145'=>array(
  'station'=>'Trappes',
  'lat'=>'48.7744',
  'lon'=>'2.0097',
  'altitude'=>'167'),

  '07110'=>array(
  'station'=>'Brest-Guipavas LFRB',
  'lat'=>'48.4442',
  'lon'=>'-4.4119',
  'altitude'=>'94'),

  '07761'=>array(
  'station'=>'Ajaccio LFKJ',
  'lat'=>'41.9236',
  'lon'=>'8.8029',
  'altitude'=>'5'),

  '07645'=>array(
  'station'=>'Nîmes-Courbessac LFME',
  'lat'=>'43.8539',
  'lon'=>'4.4131',
  'altitude'=>'69'),

  '07510'=>array(
  'station'=>'Bordeaux-Mérignac LFBD',
  'lat'=>'44.8306',
  'lon'=>'-0.6914',
  'altitude'=>'47'),

  '89642'=>array(
  'station'=>'Dumont D\'Urville',
  'lat'=>'-66.6630',
  'lon'=>'140.0003',
  'altitude'=>'43'),

  '61998'=>array(
  'station'=>'Kerguelen',
  'lat'=>'-49.3522',
  'lon'=>'70.2433',
  'altitude'=>'29'),

  '81405'=>array(
  'station'=>'Rochambeau SOCA',
  'lat'=>'4.8222',
  'lon'=>'-52.3653',
  'altitude'=>'4'),

  '91925'=>array(
  'station'=>'Hiva-Oa',
  'lat'=>'-9.8061',
  'lon'=>'-139.0356',
  'altitude'=>'61'),

  '91938'=>array(
  'station'=>'Faa\'a',
  'lat'=>'-17.5553',
  'lon'=>'-149.6144',
  'altitude'=>'2'),

  '91958'=>array(
  'station'=>'Rapa',
  'lat'=>'-27.6183',
  'lon'=>'-144.3347',
  'altitude'=>'2'),

  '91592'=>array(
  'station'=>'Nouméa NWWN',
  'lat'=>'-22.2761',
  'lon'=>'166.4528',
  'altitude'=>'70')
);

$station=$stations[$sid]['station'];
$lat=$stations[$sid]['lat'];
$lon=$stations[$sid]['lon'];
$altitude=$stations[$sid]['altitude'];

$ry=substr($run, 0, 4);
$rm=substr($run, 4, 2);
$rd=substr($run, 6, 2);
$rh=substr($run, 8, 2);
$run_time=mktime($rh, 0, 0, $rm, $rd, $ry, 0);
$run_time-=3600;
$date=date('d-m-Y H:i', $run_time).' UTC';

function y_to_press($y) {
  return 1621.864*exp(-0.000385649*$y);
}

function xy_to_temp($x, $y) {
  $a=1.04176;
  $b=0.0136993618;
  $c=-18.305737856;
  $temp=$b*($x-$a*$y)+$c;
  return $temp;
}


$svg=file_get_contents("$sid.$run.svg");

//temp

$pattern='/<path\s*d="m\s([^"]*)"[\w\d\s="-:;]*stroke:#000000;stroke-width:5.6[\w\d\s="-:;]*dasharray:none[\w\d\s="-:;]*\/>/im';
preg_match($pattern, $svg, $out);

$temp_path=explode(' ', $out[1]);
$temp_xy=array(explode(',', $temp_path[0]));

$ntemp=count($temp_path);

for ($i=1; $i<$ntemp; $i++) {
  $coord=explode(',', $temp_path[$i]);
  $temp_xy[$i][0]=$temp_xy[$i-1][0]+$coord[0];
  $temp_xy[$i][1]=$temp_xy[$i-1][1]+$coord[1];
}



//td

$pattern='/<path\s*d="m\s([^"]*)"[\w\d\s="-:;]*stroke:#0000ff;stroke-width:5.6[\w\d\s="-:;]*dasharray:none[\w\d\s="-:;]*\/>/im';
preg_match($pattern, $svg, $out);

$td_path=explode(' ', $out[1]);
$td_xy=array(explode(',', $td_path[0]));

$ntd=count($td_path);

for ($i=1; $i<$ntd; $i++) {
  $coord=explode(',', $td_path[$i]);
  $td_xy[$i][0]=$td_xy[$i-1][0]+$coord[0];
  $td_xy[$i][1]=$td_xy[$i-1][1]+$coord[1];
}


$data="# ====================================
# Radiosondage Météo-France
# ====================================
#
# Station:\t$station
# Latitude:\t$lat
# Longitude:\t$lon
# Altitude:\t$altitude
# Date:\t$date
#
# Fichier original: 
# https://donneespubliques.meteofrance.fr/donnees_libres/Pdf/RS/$sid.$run.pdf
#
# Ces valeurs sont issues d'une lecture automatique.
# Nous ne pouvons pas garantir qu'elles soient
# 100% conformes aux mesures de Météo-France.
#
# P  : Pression (hPa)
# T  : Température (°C)
# Td : Point de Rosée (°C)
#\n";
$data.="P\tT\tTd\n";
for ($i=0; $i<$ntemp; $i++) {
  $x_temp=$temp_xy[$i][0];
  $y_temp=$temp_xy[$i][1];
  $temp=round(xy_to_temp($x_temp, $y_temp), 1);
  $press=round(y_to_press($y_temp));
  
  $data .= "$press\t$temp";
  if ($i < $ntd) {
    $x_td=$td_xy[$i][0];
    $y_td=$td_xy[$i][1];
    if ($y_td != $y_temp) {
      echo "ERROR : Y_TD != Y_TEMP";
      exit(-1);
    }
    $td=round(xy_to_temp($x_td, $y_td), 1);
    $data .= "\t$td";
  }
  $data .= "\n";
}

file_put_contents("$sid.$run.txt", $data);