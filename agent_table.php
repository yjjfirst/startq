<?php
global $config_agents;
global $debugging_agents;
include_once './agent_xtable.php';
include_once './group_debugging_data.php';

$agents=$debugging_agents;
//print_r($agents);
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
				foreach($agent_column_names as $title)
				{
				?>
					<th><?php echo $title?></th>
				<?php
				}
				?>
			</tr>
			<?php
			for($j=0;$j<count($agent);$j++,next($agent))
			{
				$agent_name=key($agent);
			?>
				<tr>
				    <td><?php echo $agent_name?></td>
					<?php
					$number=$agent["$agent_name"];
					$number = agent_secs_to_strtime($number);
					foreach($number as $item)
					{
					?>
						<td><?php echo $item?></td>
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
