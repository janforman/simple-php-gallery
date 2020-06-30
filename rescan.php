<?php
header("Expires: 0");
$dir = "files";
$response = scan($dir);

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
$txt = json_encode($response);
$fopen = fopen("index.json", "w") or die("Unable to open file!");
fwrite($fopen, $txt);
fclose($fopen);
echo 'OK Total files: ' . $c;

function gps($coordinate, $hemisphere) {
  if (is_string($coordinate)) {
    $coordinate = array_map("trim", explode(",", $coordinate));
  }
  for ($i = 0; $i < 3; $i++) {
    $part = explode('/', $coordinate[$i]);
    if (count($part) == 1) {
      $coordinate[$i] = $part[0];
    } else if (count($part) == 2) {
      $coordinate[$i] = floatval($part[0])/floatval($part[1]);
    } else {
      $coordinate[$i] = 0;
    }
  }
  list($degrees, $minutes, $seconds) = $coordinate;
  $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
  return $sign * ($degrees + $minutes/60 + $seconds/3600);
}
