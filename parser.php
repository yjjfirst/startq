<?php
// Parse without sections
//$ini_array = parse_ini_file("queues.conf");
//print_r($ini_array);

// Parse with sections
$ini_array = parse_ini_file("queues.conf", true);
//print_r($ini_array); 

?>
