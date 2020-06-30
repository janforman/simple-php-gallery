<?php
$json = json_decode(file_get_contents('index.json'), TRUE);

$c=0;
foreach ($json as $value) {
    foreach ($value['items'] as $pictures) {
    $filename = $pictures['path'];
    if (!file_exists('cache/'.md5($filename).'.jpg')) { resize($filename); $c++; }
    }
}
echo "OK ".$c;
function resize($filename) {
list($width, $height) = getimagesize($filename);
$ratio = $width / $height;
$newwidth = 500;
$newheight = 500 / $ratio;
$thumb = imagecreatetruecolor($newwidth, $newheight);
$source = imagecreatefromjpeg($filename);
imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
imagejpeg($thumb,'cache/'.md5($filename).'.jpg',92);
}

?>