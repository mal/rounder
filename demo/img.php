<?php

 require 'qs.php';
 require '../src/RoundedImage.php';

 $f = 'input/desert.jpg';
 $r = '10, 0';

 $qs = new Querystring();

 if ( $qs->exists('f') )
 {
  $file = "input/" . $qs->get('f');
  if ( file_exists($file) )
  {
   $f = $file;
   $save = pathinfo($qs->get('f'), PATHINFO_FILENAME);
  }
 }
 if ( $qs->exists('r') )
 {
  $r_tmp = preg_replace('/(\d+)(?:,(\d+)(?:,(\d+)(?:,(\d+))?)?)?/', '$1,$2,$3,$4', $qs->get('r'), -1, $c);
  if ( $c > 0 )
   $r = explode(',', $r_tmp);
 }

 for ( $i = 0; $i < 4; $i++ )
 {
  if ( !isset($r[$i]) || $r[$i] == '' )
   $r[$i] = null;
 }

 try
 {
  $im = new RoundedImage( $f );
  $im->roundCorners($r[0], $r[1], $r[2], $r[3]);
  $png = $im->getImageData();
 }
 catch (Exception $e)
 {
  print $e->getMessage();
  exit;
 }

 header ("Content-type: image/png");
 header ("Content-disposition: attachment; filename={$save}_rounded.png");
 print $png;

?>
