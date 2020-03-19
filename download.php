<?php
function download($url,$filename){
    global $conn;
  $file = file_get_contents($url);
  $filename = $filename . ".mp3";
  file_put_contents("./music/" . $filename,$file);
  return "https://example.com/music/$filename";
}