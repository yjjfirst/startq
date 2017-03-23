<?php
include_once './parser.php';
include_once './xtable.php';

//////////////////////////////////////////////////////////////////////////////
$parser = new parser();
$group_table = new xtable();

$group_table->set_init_values($parser->get_group_init_values());
$group_table->set_time_items($parser->get_group_time_items());
$group_table->set_column_names($parser->get_group_cloumn_names());
$group_table->set_array_objs($parser->get_groups_objs());
$group_table->set_default_values();
$group_table->retrive_from_asterisk();
/////////////////////////////////////////////////////////////////////////////
$groups=$group_table->get_array_objs();

for($i=0;$i<count($groups);next($groups),$i++)
{
	//print_r($groups
	$group_name = key($groups);
	$total_items=array();
	$queues = current($groups);
?>
<!-- Table goes in the document BODY -->
		<table class="imagetable">
			<tr>
				<th><?php echo "Group Name: ".$group_name?></th>
			</tr>
			<tr>
				<?php
				foreach($group_table->column_names as $title)
				{
				?>
					<th><?php echo $title?></th>
				<?php
				}
				?>
			</tr>
			<?php
			$rows = 0;
			$td_class = "class=\"odd\"";
			for($k=0;$k<count($queues);$k++,next($queues))
			{
				$rows++;
				$queue_name=key($queues);
				if($rows%2 == 0)
				{	 
					$td_class="class=\"even\"";
				}
				else
				{
					$td_class="class=\"odd\"";
				}
			?>
				<tr>
				    <td <?php echo $td_class?>><?php echo $queue_name?></td>
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
					$queue = $group_table->secs_to_strtime($queue);
					foreach($queue as $item)
					{
					?>
						<td <?php echo $td_class?>><?php echo $item?></td>
					<?php
					}
					?>
				</tr>
			<?php
			}
			$total_items = $group_table->secs_to_strtime($total_items);
			$rows++;
			if($rows%2 == 0)
			{	 
				$td_class="class=\"even\"";
			}
			else
			{
				$td_class="class=\"odd\"";
			}
			?>
			<tr>
				<td <?php echo $td_class?>><?php echo "Total"?></td>
				<?php
				foreach($total_items as $item)
				{
				?>
					<td <?php echo $td_class?>><?php echo $item?></td>
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
