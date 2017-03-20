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
table.imagetable td {
	background:#dcddc0 url('./images/cell-grey.jpg');
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.yellow{
	background:#FFCC00;
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}
	
table.imagetable td.red{
	background:#FF0000;
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}

table.imagetable td.green{
	background:#00FF00;
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}

</style>

<body>
<!-- Table goes in the document BODY -->
<table class="imagetable">
<tr>
<th>Group Name</th>
</tr>
<tr>
	<th>Info Header 1</th><th>Info Header 2</th><th>Info Header 3</th>
</tr>
<tr>
	<td class="yellow">Text 1A</td><td class="red">Text 1B</td><td class="green">Text 1C</td>
</tr>
<tr>
	<td>Text 2A</td><td>Text 2B</td><td>Text 2C</td>
</tr>
</table>
</body>
</html>
