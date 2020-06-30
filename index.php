<?php
function main()
{
  echo '<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="index.css?r=1.02" type="text/css" />
  <link rel="stylesheet" href="assets/fancybox/jquery.fancybox.min.css" />
  <script src="assets/js/jquery-1.10.0.min.js"></script>
  <script src="assets/fancybox/jquery.fancybox.min.js" charset="UTF-8"></script>
  <link rel="manifest" href="manifest.json">
  <link rel="stylesheet" href="assets/leaflet/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
  <script src="assets/leaflet/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
  <title>Gallery</title>
</head>

<body>
  <div class="sticky">
  <h1><a href="?">HOME</a></h1> - ';

  $json = json_decode(file_get_contents('index.json'), TRUE);
  $setview = '49.77, 13.60';

  $folder = explode('/', $_GET['f']);
  $folder = $folder[1];
  $marker = '';

  if ($json[$folder]['title']) echo '<h2>' . $json[$folder]['title'] . '</h2> - ';
  if ($json[$folder]['readme']) echo $json[$folder]['readme'] . ' - ';

  echo '<a href="https://github.com/janforman/">SimplePHP Galery © 2020 Jan Forman</a><br/>';
  echo '</div>    <div class="clearfix"></div>';

  if (!$_GET['f']) {
    foreach ($json as $value) {
      if ($value['type'] == 'folder') {
        echo "<a href='?f=" . $value['path'] . "'><h2>" . $value['title'] . "</h2></a> | ";
      }
    }
  }

  foreach ($json[$folder]['items'] as $value) {
    if ($value['type'] == 'file') {
      $marker .= "L.marker([" . $value['gps'] . "]).addTo(map).bindPopup('<img src=\"cache/" . md5($value['path']) . ".jpg\" class=\"mapthumb\"/>');";
      $setview = $value['gps'];

      echo '<div class="responsive"><div class="gallery">
      <a target="_blank" href="' . $value['path'] . '" data-fancybox="images" data-caption="' . $value['title'] . '">
      <img src="cache/' . md5($value['path']) . '.jpg" alt="' . $value['title'] . '"></a>
    <div class="desc">' . $value['title'] . '<br/>' . round($value['size'] / 1024 / 1024, 2) . ' MB</div>
  </div>
</div>
';
    }
  }

  echo '<div class="clearfix"></div>';

  showmap($setview, $marker);

  echo "<script type='text/javascript'>$.fancybox.defaults.buttons = ['zoom', 'slideShow', 'download', 'close'];</body></html>";
}

function rescan()
{
  header("Expires: 0");
  $dir = "files";
  $response = scan($dir);

  $txt = json_encode($response);
  $fopen = fopen("index.json", "w") or die("Unable to open file!");
  fwrite($fopen, $txt);
  fclose($fopen);
  echo 'Indexed OK <a href="?do=thumbs">generate thumbnails</a>';
}

function thumbs()
{
  $json = json_decode(file_get_contents('index.json'), TRUE);

  $c = 0;
  foreach ($json as $value) {
    foreach ($value['items'] as $pictures) {
      $filename = $pictures['path'];
      if (!file_exists('cache/' . md5($filename) . '.jpg')) {
        resize($filename);
        $c++;
      }
    }
  }
  echo "OK " . $c;
}

// functions
function scan($dir)
{
  $files = array();
  global $c;
  if (file_exists($dir)) {

    foreach (scandir($dir) as $f) {
      if (!$f || $f[0] == '.' || $f == 'Thumbs.db' || $f == 'README.txt') {
        continue;
      }
      if (is_dir($dir . '/' . $f)) {
        $title = $f;
        $description = '';
        if (file_exists($dir . '/' . $f . '/README.txt')) {
          $d = file_get_contents($dir . '/' . $f . '/README.txt');
          $d = explode('|', $d);
          $title = $d[0];
          $description = $d[1];
        }
        $files["$f"] = array("name" => $f, "type" => "folder", "path" => $dir . '/' . $f, "title" => $title, "readme" => $description, "items" => scan($dir . '/' . $f));
      } else {
        $title = $f;

        $exif = exif_read_data($dir . '/' . $f);
        $lat = gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
        $lon = gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);

        $files[]  = array("name" => $f, "type" => "file", "path" => $dir . '/' . $f, "title" => $title, "gps" => "$lat,$lon", "size" => sprintf("%u", filesize($dir . '/' . $f)));
        $c++;
      }
    }
  }
  return ($files);
}

function resize($filename)
{
  list($width, $height) = getimagesize($filename);
  $ratio = $width / $height;
  $newwidth = 500;
  $newheight = 500 / $ratio;
  $thumb = imagecreatetruecolor($newwidth, $newheight);
  $source = imagecreatefromjpeg($filename);
  imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
  imagejpeg($thumb, 'cache/' . md5($filename) . '.jpg', 92);
}

function showmap($setview, $marker)
{

  echo '<div id="map"></div><script type="text/javascript">var map = L.map("map").setView([' . $setview . '], 10);
L.tileLayer("https://data.hzspk.cz/{z}/{x}/{y}.png", {attribution: \'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\'}).addTo(map);';

  echo $marker;
  echo "</script>";
}

function gps($coordinate, $hemisphere)
{
  if (is_string($coordinate)) {
    $coordinate = array_map("trim", explode(",", $coordinate));
  }
  for ($i = 0; $i < 3; $i++) {
    $part = explode('/', $coordinate[$i]);
    if (count($part) == 1) {
      $coordinate[$i] = $part[0];
    } else if (count($part) == 2) {
      $coordinate[$i] = floatval($part[0]) / floatval($part[1]);
    } else {
      $coordinate[$i] = 0;
    }
  }
  list($degrees, $minutes, $seconds) = $coordinate;
  $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
  return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
}


////// loader
switch ($_GET['do']) {
  case 'rescan';
    rescan();
    break;
  case 'thumbs';
    thumbs();
    break;
  default;
    main();
    break;
}
