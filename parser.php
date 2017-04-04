<?php
class parser
{	
    private $ini_array,$group_objs,$agent_objs,$group_column_color,$agent_column_color;
	
    private static $_instance=null;

    private function __construct()
    {

    }
    private function __clone()
    {
    }
    
    public static function get_instance($conf_path='queues.conf')
    {
        if(is_null(self::$_instance))
        {
            self::$_instance = new parser();
            self::$_instance->ini_array = parse_ini_file($conf_path, true);
        }

        return self::$_instance;
    }
       
	///////////////////////////////////////////////////////////////////////////////////	
	private function parse_to_groupobj(&$data_source, $name, $row_names_str)
	{
		$new_table = array();
		$table_name = $name;
		$row_to_add = explode(",",$row_names_str);
		while($_name = current($row_to_add)) 
		{ 
		 $new_row = array();
		 $new_table["$_name"]=$new_row;      
		 next($row_to_add);
		}
		unset($data_source["$table_name"]);
		$data_source["$table_name"]=$new_table;
	}
	private function build_group_objs()
	{
	   $this->group_objs = $this->ini_array["groups"];	
	    
	   $fetch_src = $this->group_objs;	
	   foreach($fetch_src as $key=>$value)
	   {
		   $this->parse_to_groupobj($this->group_objs,$key,$value);
	   }
	   return $this->group_objs;
	}
	private function build_group_color_objs()
	{
		$this->group_column_color = $this->ini_array["queue-colors"];
		foreach($this->group_column_color as $i=>$color_range)
		{
		    $range_obj = explode(",",$color_range);
			unset($this->group_column_color[$i]);
			foreach($range_obj as $j=>$color_range)
			{
				$min=-1;
				$max=-1;
				$color='unset';
				
				unset($range_obj[$j]);
				sscanf($color_range, "[%d-%d]%s",$min, $max, $color);
				//echo "min=$min, max=$max, color=$color\n";
				$range_obj[$color]=array($min,$max);
				//print_r($range_obj);
			}
			$this->group_column_color[$i]=$range_obj;
		}
		return $this->group_column_color;
	}
	///////////////////////////////////////////////////////////////////////////////////
	public function get_group_init_values()
	{
		return array("0","0","0","0","0","0","0","0","0");
	}
	public function get_group_time_items()
	{
		return array(1,5);
	}
	public function get_group_cloumn_names()
	{
	    return array("Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail");
	}
	public function get_groups_objs()
	{
		return $this->build_group_objs();
	}
	public function get_group_collors()
	{
		return $this->build_group_color_objs();
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////
	private function parse_to_agentobj(&$data_source)
	{	
		foreach($data_source as $agent=>$queues)
		{
			$queue_row = explode(",",$queues);
			foreach($queue_row as $index=>$queue)
			{
			    $queue_name = $agent."-".$queue;
				//echo "queue_name = $queue_name\n";
				$new_row[$queue_name]=array();
			}
			unset($data_source[$agent]);
		}
		$data_source["Agents"]= array();
		$data_source["Agents"] = $new_row;
	}
	private function build_agent_color_objs()
	{
		$this->agent_column_color = $this->ini_array["agent-colors"];
		foreach($this->agent_column_color as $i=>$_colors)
		{
			unset($this->agent_column_color[$i]);
			$_colors = explode(",",$_colors);
			$this->agent_column_color[$i]=$_colors;
		}
		return $this->agent_column_color;
	}
	private function build_agent_objs()
	{
	   $this->agent_objs = $this->ini_array["agents"];	
	   $this->parse_to_agentobj($this->agent_objs);
	   
	   return $this->agent_objs;
	}
	///////////////////////////////////////////////////////////////////////////////////	
	public function get_agent_init_values()
	{
		return array("0","0","0","0","0","0","0","0");
	}
	public function get_agent_time_items()
	{
		return array(1,7);
	}
	public function get_agent_cloumn_names()
	{
		return array("Agents","ACD STATE","ACD State Start Time","ACD State Duration","Inbound Calls","Outgoing Calls","Answered Calls","Bounced Calls","Transferred Calls","Average Call Duration");
	}
	public function get_agents_objs()
	{
		return $this->build_agent_objs();
	}
	public function get_agent_colors()
	{
		return $this->build_agent_color_objs();
	}
	/*
	•	Pas logé (Not logged in Queue)
	•	Hold (Hold)
	•	Disponible (Available) 
	•	Occupé (Busy)
	•	Pause (Agent is on paused status)
	*/
	public function get_agent_state_str($index)
	{
		//$state_str = array('Pas logé','Hold','Disponible','Occupé','Pause');
		$state_str = array('Not logged in Queue','Hold','Available','Busy','Agent is on paused status');
		if($index >= 0 && $index < count($state_str))
		{
			return $state_str[$index];
		}

		return 'Unknown';
	}
    ///////////////////////////////////////////////////////////////////////////////////
    public function get_asterisk_options()
    {
        return $this->ini_array['asterisk'];
    }
    //////////////////////////////////////////////////////////////////////////////////
}
//print_r (parser::get_instance()->get_agent_colors());
?>
