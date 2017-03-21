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

<?php
global $group_tables;
include_once './group_xtable.php';

?>
<body>
<?php
foreach($group_tables as $table)
{
	$group_name = key($table);
	$total=array();
	array_push($total,"Total");
	$rows = current($table);
?>
<!-- Table goes in the document BODY -->
		<table class="imagetable">
			<tr>
				<th><?php echo $group_name?></th>
			</tr>
			<tr>
				<?php
				foreach($group_column_names as $title)
				{
				?>
					<th><?php echo $title?></th>
				<?php
				}
				?>
			</tr>
			<?php
			foreach($rows as $row)
			{
				if($row == 10)
				{
					//skip the obj 10;
					continue;
				}
			?>
				<tr>
					<?php
					for($j=1;$j<count($row);$j++)
					{
						if($j>=count($total))
						{
							array_push($total,$row[$j]);  
						}
						else
							$total[$j]+=$row[$j];
					}
					$row = secs_to_strtime($row);
					foreach($row as $item)
					{
					?>
						<td><?php echo $item?></td>
					<?php
					}
					?>
				</tr>
			<?php
			}
			$total = secs_to_strtime($total);
			foreach($total as $item)
			{
			?>
				<td><?php echo $item?></td>
			<?php
			}
			?>
			<tr>
				<td class="yellow">Text 1A</td><td class="red">Text 1B</td><td class="green">Text 1C</td>
			</tr>
			<tr>
				<td>Text 2A</td><td>Text 2B</td><td>Text 2C</td>
			</tr>
		</table>
<?php
}
?>
</body>
</html>
