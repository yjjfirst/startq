<?php
// Parse without sections
//$ini_array = parse_ini_file("queues.conf");
//print_r($ini_array);

// Parse with sections
$ini_array = parse_ini_file("queues.conf", true);
//print_r($ini_array);


function time_strformat($secs) 
{
  $hour = floor($secs/3600);
  $minute = floor(($secs-3600*$hour)/60);
  $second = floor((($secs-3600*$hour)-60*$minute)%60);
  $ret_fmt= sprintf("%02d:%02d:%02d", $hour,$minute,$second);
  
  return $ret_fmt;
}

?>
