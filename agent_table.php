<?php
global $debugging_agents;
include_once './xtable.php';
include_once './debugging_data.php';
/////////////////////////////////////////////
$parser = new parser();
$parser->init();

$agent_table = new xtable();
$agent_table->set_init_values($parser->get_agent_init_values());
$agent_table->set_time_items($parser->get_agent_time_items());
$agent_table->set_column_names($parser->get_agent_cloumn_names());
$agent_table->set_array_objs($parser->get_agents_objs());

$agent_table->set_defaults();
/////////////////////////////////////////////

$agents=$agent_table->get_array_objs();
for($i=0;$i<count($agents);next($agents),$i++)
{
	//print_r($agents
	$agent_name = key($agents);
	$agent = current($agents);
?>
<!-- Table goes in the document BODY -->
		<table class="imagetable">
			<tr>
				<th><?php echo "Agent Name: ".$agent_name?></th>
			</tr>
			<tr>
				<?php
				foreach($agent_table->column_names as $title)
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
			for($j=0;$j<count($agent);$j++,next($agent))
			{
				$rows++;
				if($rows%2 == 0)
			    {	 
				    $td_class="class=\"even\"";
			    }
			    else
			    {
				    $td_class="class=\"odd\"";
			    }
				$agent_name=key($agent);
			?>
				<tr>
				    <td <?php echo $td_class?>><?php echo $agent_name?></td>
					<?php
					$number=$agent["$agent_name"];
					$number = $agent_table->secs_to_strtime($number);
					foreach($number as $item)
					{
					?>
						<td <?php echo $td_class?>><?php echo $item?></td>
					<?php
					}
					?>
				</tr>
			<?php
			}
			?>
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