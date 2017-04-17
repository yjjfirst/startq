<html>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<!--<meta name="viewport" content="width=640" />-->
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="">
<meta name="format-detection" content="telephone=no">
<title> </title>
<script src="jquery-1.12.4.js"></script>
<script src="flowtype.js"></script>
<link rel="stylesheet" href="./style.css">
<!-- CSS goes in the document HEAD or added to your external stylesheet -->
<script language="JavaScript"> 
function page_reload() 
{ 
/*
     var httpxml;
	 if (window.XMLHttpRequest) 
	 {
        xmlhttp = new XMLHttpRequest();
     }
	 else
	 {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
     }
     xmlhttp.onreadystatechange = function()
	 {
         if(4 == xmlhttp.readyState && 200 == xmlhttp.status) 
		 {
              document.getElementById("ajax_result").innerHTML = xmlhttp.responseText;
         }
	 }
	 xmlhttp.open("GET","index.php");
     xmlhttp.send();
*/
    window.location.reload(); 
} 
setTimeout('page_reload()',3000); 
</script> 
<body>
<script type="text/javascript"> 
        $j = jQuery.noConflict();
        $j(document).ready(function() {
$j('table').flowtype({
minFont: 5,
fontRatio : 90,
lineRatio : 1.45
});
});
</script>
<?php
include_once("./group_table.php");
?>
<br/>
<script type="text/javascript"> 
        $j = jQuery.noConflict();
        $j(document).ready(function() {
$j('table').flowtype({
minFont: 5,
fontRatio : 90,
lineRatio : 1.45
});
});
</script>
<?php
include_once("./agent_table.php");
?>
</body>
</html>
