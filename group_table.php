<?php
global $debugging_groups;
include_once './xtable.php';
include_once './debugging_data.php';
//////////////////////////////////////////////////////////////////////////////

$group_table = new xtable();
$group_table->data_source=$ini_array["groups"];
$group_table->column_names = array("Queue Id","Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail","Outgoing calls");
$group_table->init_values=array("0","0","0","0","0","0","0","0","0");
$group_table->time_items=array(1,5);
$conf_groups = $group_table->build_table();

/////////////////////////////////////////////////////////////////////////////
$groups=$conf_groups;

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
