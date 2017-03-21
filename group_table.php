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
global $config_groups;
global $debugging_groups;
include_once './group_xtable.php';
include_once './group_debugging_data.php';

$groups=$debugging_groups;
//print_r($groups);
?>
<body>
<?php
for($i=0;$i<count($groups);next($groups),$i++)
{
	//print_r($groups
	$group_name = key($groups);
	$total_items=array();
	array_push($total,"Total");
	$queues = current($groups);
?>
<!-- Table goes in the document BODY -->
		<table class="imagetable">
			<tr>
				<th><?php echo "Group Name: ".$group_name?></th>
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
			for($i=0;$i<count($queues);$i++,next($queues))
			{
				$queue_name=key($queues);
			?>
				<tr>
				    <td><?php echo $queue_name?></td>
					<?php
					$queue=$queues["$queue_name"];
					for($j=0;$j<count($queue);$j++)
					{
						if($j>=count($total_items))
						{
							array_push($total_items,$queue[$j]);  
						}
						else
							$total_items[$j]+=$queue[$j];
					}
					$queue = secs_to_strtime($queue);
					foreach($queue as $item)
					{
					?>
						<td><?php echo $item?></td>
					<?php
					}
					?>
				</tr>
			<?php
			}
			$total_items = secs_to_strtime($total_items);
			?>
			<tr>
				<td><?php echo "Total"?></td>
				
				<?php
				foreach($total_items as $item)
				{
				?>
					<td><?php echo $item?></td>
				<?php
				}
				?>
			</tr>
<!--
			<tr>
				<td class="yellow">Text 1A</td><td class="red">Text 1B</td><td class="green">Text 1C</td>
			</tr>
			<tr>
				<td>Text 2A</td><td>Text 2B</td><td>Text 2C</td>
			</tr>
-->
		</table>
<?php
}
?>
</body>
</html>
