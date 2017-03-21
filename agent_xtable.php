<?php
include_once("./parser.php");
$config_agents = $ini_array["agents"];

$agent_column_names = array("Agent Id","ACD STATE","ACD State Start Time","ACD State Duration","Inbound Calls","Answered Calls","Bounced Calls","Transferred Calls","Average Call Duration");
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function agent_secs_to_strtime(&$row)
{
	for($i=0;$i<count($row);$i++)
	{
		if($i == 2 || $i == 7)
		{
			$row[$i]= time_strformat($row[$i]);
		}
	}
	return $row;
}
function _init_agent_row(&$rows)
{
	$data_raw_init_values = array("0","0","0","0","0","0","0","0"); 

	for($i=0;$i<count($data_raw_init_values);$i++)
	{
		array_push($rows,$data_raw_init_values[$i]);
	}
	//print_r($rows);
}

function conf_agent_table(&$agent,&$agent_name, &$rows)
{
	$table = array();
	$agent_name = key($agent);
	$queue_name = current($agent);
	$queue_row = explode(",",$queue_name);
	//echo "agent_name = ".$agent_name."\n";
    //print_r($agent);
	while($_name = current($queue_row)) 
    { 
	 $row = array();
     //$row[0] = $_name;
	 _init_agent_row($row);
	 $table["$_name"]=$row;      
     next($queue_row);
    }
	unset($agent["$agent_name"]);
	//echo "after delete agent\n";
	//print_r($agent);
	$agent["$agent_name"]=$table;
	//echo "after added $agent_name to agent\n";
	//print_r($agent);
}

function build_agent_table(&$conf_agents,$_column_names)
{
   $i=0;
   
   while($agent = current($conf_agents) && $i < count($conf_agents))
   {
	   $rows=array(count($_column_names));
	   conf_agent_table($conf_agents, $agent_name, $rows);
       $i++;	
   }
    $agent_tables = $conf_agents;
  }
build_agent_table($config_agents,$agent_column_names);
//print_r($config_agents);
//show_agent_html_table($agent_tables,$agent_column_names);

?>