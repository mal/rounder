<?php

require 'qs.php';

// Check For Uploaded Image
if ( isset($_FILES['u']) )
{
 $upload = $_FILES['u'];
 if ( @getimagesize($upload['tmp_name']) )
  move_uploaded_file($upload['tmp_name'], 'input/' . $upload['name']);
}

$f = 'unwelcome.jpg';
$r = '0, 75';

$qs = new Querystring();

if ( $qs->exists('f') )
{
 $file = "input/" . $qs->get('f');
 if ( file_exists($file) )
 {
  $f = $qs->get('f');
 }
}
if ( $qs->exists('r') )
{
 $r_tmp = preg_replace('/(\d+)(?:,(\d+)(?:,(\d+)(?:,(\d+))?)?)?/', '$1, $2, $3, $4', $qs->get('r'), -1, $c);
 if ( $c > 0 )
  $r = trim($r_tmp, ', ');
}

// Get Potential Input
$dir = new DirectoryIterator('input/');
$options = '';

foreach($dir as $file)
{
 // Check Found Item Is A File
 if ($file->isFile())
 {
  $name = $file->getFilename();
  if ( $name != $f )
   $options .= "\n      <option value=\"$name\">$name</option>";
  else
   $options .= "\n      <option value=\"$name\" selected=\"selected\">$name</option>";
 }
}
$options .= "\n     ";
?>
<html>
 <head>
  <title>Image Rounder</title>
  <style type="text/css">
   body,
   html {
    background: url(bg.gif) repeat;
    font: normal 11px verdana, tahoma, arial, sans-serif;
    margin: 0;
    padding: 0;
   }
   form {
    clear: both;
   }

   div.clear {
    clear: both;
    height: 0;
    line-height: 0;
    overflow: hidden;
   }

   div.welcome {
    background: white;
    border: 1px solid #ccc;
    float: left;
    font-weight: bold;
    margin: 8px 0 0 8px;
    padding: 205px 0;
    text-align: center;
    width: 422px;
   }

   img.image {
    float: left;
    margin: 8px;
   }

   div.settings {
    background: white;
    border-right: 1px solid #ccc;
    float: left;
    height: 100%;
    padding: 0 10px;
    width: 331px;
    _width: 351px;
   }
   div.settings var {

   }
   div.settings var.tl {
    color: #F03030;
   }
   div.settings var.tr {
    color: #F08030;
   }
   div.settings var.bl {
    color: #C000C0;
   }
   div.settings var.br {
    color: #6090C0;
   }
   div.settings li {
    clear: both;
   }
   div.settings li span,
   div.settings li var.small {
    display: block;
    float: left;
    margin: 0 4px 0 0;
    width: 185px;
   }
   div.settings li var.small {
    text-align: right;
    width: 85px;
   }
   div.settings a {
    color: #30C090;
    display: block;
    float: left;
    margin: 4px 0;
   }
   div.settings input[type='submit'] {
    clear: both;
    float: right;
    margin-right: 1px;
    width: 76px;
   }
   div.settings input.download {
    font: bold 10px tahoma, arial, sans-serif;
   }

   div.field {
    clear: both;
    margin: 10px 0;
   }
   div.field label {
    display: block;
    float: left;
    font-weight: bold;
    margin: 5px 0;
    width: 95px;
   }
   div.field input[type='text'],
   div.field select {
    float: right;
    font-size: 11px;
    font: normal 12px tahoma, arial, sans-serif;
    margin: 2px 0;
    width: 223px;
   }
   div.field input[type='text'] {
    padding: 0 3px;
   }
   div.field input[type='file'] {
    float: right;
    margin-right: 1px;
   }
  </style>
  <script type="text/javascript">
   var $firstRun = true;
   var $download = false;
   function getImage()
   {
    var $r = document.getElementById('r').value.replace(/\s/g, '');

    if ( $download )
    {
     $download = false;
     $form_r = document.getElementById('form_r');
     $form_r.value = $r;
     return true;
    }

    var $f = document.getElementById('f').value;
    var $l = document.getElementById('l');
    var $i = document.getElementById('i');
    var $q = '?f=' + $f + '&r=' + $r;

    if ( $firstRun )
    {
     var $w = document.getElementById('w');
     $w.style.display = 'none';
     $i.style.display = 'block';
     $l.innerHTML = 'Link to this image';
     $firstRun = false;
    }

    $l.href = $q;
    $i.src = 'img.php' + $q;

    return false;
   }
  </script>
 </head>
 <body>
  <div class="settings">
   <h1>Image Rounder</h1>
   <form onsubmit="return getImage()" action="img.php" method="post">
    <p>Select the image you wish to round, or upload a new one. When you are ready to begin, enter the radii of the curves you want. The radii field below takes up to four comma seperated values, in the order:</p>
    <p style="font-style: italic;"><var class="tl">top-left</var> [, <var class="tr">top-right</var> [, <var class="bl">bottom-left</var> [, <var class="br">bottom-right</var> ]]]</p>
    <p>When not explicitly set;</p>
    <ul>
     <li><var class="small tr">top-right</var><span>defaults to <var class="tl">top-left</var></span></li>
     <li><var class="small bl">bottom-left</var><span>defaults to <var class="tl">top-left</var></span></li>
     <li><var class="small br">bottom-right</var><span>defaults to <var class="tr">top-right</var></span></li>
    </ul>
    <div class="clear"></div>
    <div class="field">
     <label>Filename:</label>
     <select id="f" name="f"><?php print $options; ?></select>
     <div class="clear"></div>
    </div>
    <div class="field">
     <label>Radii:</label>
     <input id="r" type="text" value="<?php print $r; ?>"/>
     <input id="form_r" name="r" type="hidden" value="<?php print $r; ?>"/>
     <div class="clear"></div>
    </div>
    <div>
     <input type="submit" name="action" value="Run Test"/>
     <a id="l"></a>
     <div class="clear" style="height: 10px;"></div>
     <input class="download" type="submit" name="action" value="Download" onclick="$download = true"/>
     <div class="clear"></div>
    </div>
   </form>
   <div class="clear" style="height: 1px;"></div>
   <form method="post" enctype="multipart/form-data">
    <div class="field">
     <label>New Image:</label>
     <input type="file" name="u" size="20"/>
     <div class="clear"></div>
    </div>
    <div>
     <input type="submit" value="Upload"/>
     <div class="clear"></div>
    </div>
    <div>M. Graty &copy; 2008-2009.</div>
   </form>
  </div>
  <img id="i" class="image" src="" style="display: none;"/>
  <div id="w" class="welcome">Click 'Run Test' to generate an image</div>
 </body>
</html>
