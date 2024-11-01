<?php
session_start();
$mychar = substr(sha1(time().$crypton),10,6);
$_SESSION['thechar'] = $mychar;
$newImage = imagecreatefromjpeg("cap_risal.jpg");
$txtColor = imagecolorallocate($newImage, 0, 0, 0);
imagestring($newImage, 5, 5, 5, $mychar, $txtColor);
header("Content-type: image/jpeg");
imagejpeg($newImage);
?>
