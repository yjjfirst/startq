<?php
include_once("./parser.php");
$config_groups = $ini_array["groups"];

$group_column_names = array("Queue Id","Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail","Outgoing calls");
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function time_strformat($secs) 
{
  $hour = floor($secs/3600);
  $minute = floor(($secs-3600*$hour)/60);
  $second = floor((($secs-3600*$hour)-60*$minute)%60);
  $ret_fmt= sprintf("%02d:%02d:%02d", $hour,$minute,$second);
  
  return $ret_fmt;
}

function secs_to_strtime(&$row)
{
	for($i=0;$i<count($row);$i++)
	{
		if($i == 2 || $i == 6)
		{
			$row[$i]= time_strformat($row[$i]);
		}
	}
	return $row;
}

function _init_group_row(&$rows)
{
	$data_raw_init_values = array("","1","60","0","0","0","50","0","0","0"); 
	$i=1;
	for($i=1;$i<count($data_raw_init_values);$i++)
	{
		array_push($rows,$data_raw_init_values[$i]);
	}
	//print_r($rows);
}

function conf_group_table(&$group,&$group_name, &$rows)
{
	$table = array();
	$group_name = key($group);
	$queue_name = current($group);
	$queue_row = explode(",",$queue_name);
	//echo "group_name = ".$group_name."\n";
    //print_r($group);
	while($_name = current($queue_row)) 
    { 
	 $row = array();
     $row[0] = $_name;
	 _init_group_row($row);
	 array_push($table,$row);      
     next($queue_row);
    }
	unset($group["$group_name"]);
	//echo "after delete group\n";
	//print_r($group);
	$group["$group_name"]=$table;
	//echo "after added $group_name to group\n";
	//print_r($group);
}

function build_group_table(&$conf_groups,$_column_names)
{
   $i=0;
   
   while($group = current($conf_groups) && $i < count($conf_groups))
   {
	   $rows=array(count($_column_names));
	   conf_group_table($conf_groups, $group_name, $rows);
       $i++;	
   }
    $group_tables = $conf_groups;
  }
build_group_table($config_groups,$group_column_names);
//print_r($config_groups);
//show_group_html_table($group_tables,$group_column_names);

?>