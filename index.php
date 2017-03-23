<html>
<!-- CSS goes in the document HEAD or added to your external stylesheet -->
<style type="text/css">
table.imagetable {
	font-family: verdana,arial,sans-serif;
	font-size:11px;
	color:#333333;
	border-width: 1px;
	border-color: #999999;
	border-collapse: collapse;
}
table.imagetable th {
	background:#b5cfd2 url('./images/cell-blue.jpg');
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable th.top {
	background:#CCCCCC;
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.odd {
	background:#FFFFFF;
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.even {
	background:#F9F9F9;
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.blank {
	background:#FFFFFF;
	border-width: 1px;
	padding: 10px;
	border-style: solid;
	border-color: #FFFFFF;
}

table.imagetable td.yellow{
	background:#FFCC00;
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #999999;
}
	
table.imagetable td.red{
	background:#FF0000;
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.green{
	background:#00FF00;
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #999999;
}
</style>
<body>
<table class="imagetable" width="100%">
<tr>
<th class="top"  align="left">Call Centre Queues and Agents</th>
</tr>
<tr>
<td class="blank" width="100%"></td>
</tr>
</table>

<?php
//include_once("./xtable.php");
include_once("./group_table.php");
include_once("./agent_table.php");
?>
</body>
</html>
