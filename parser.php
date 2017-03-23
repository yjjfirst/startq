<?php
class parser
{	
	private $ini_array,$group_objs,$agent_objs,$group_column_color;
	
    private function parse_to_obj(&$data_source, $name, $row_names_str)
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
	
	public function __construct($conf_path='queues.conf')
	{
		$this->ini_array = parse_ini_file($conf_path, true);
	}
	///////////////////////////////////////////////////////////////////////////////////	
	private function build_group_objs()
	{
	   $this->group_objs = $this->ini_array["groups"];	
	    
	   $fetch_src = $this->group_objs;	
	   foreach($fetch_src as $key=>$value)
	   {
		   $this->parse_to_obj($this->group_objs,$key,$value);
	   }
	   return $this->group_objs;
	}
	private function build_agent_objs()
	{
	   $this->agent_objs = $this->ini_array["agents"];	
	    
	   $fetch_src = $this->agent_objs;	
	   foreach($fetch_src as $key=>$value)
	   {
		   $this->parse_to_obj($this->agent_objs,$key,$value);
	   }
	   return $this->agent_objs;
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
	private function build_agent_color_objs()
	{
		$group_column_color = $this->ini_array["agent-colors"];
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
		return array("Queue Id","Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail","Outgoing calls");
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
		return array("Agent Id","ACD STATE","ACD State Start Time","ACD State Duration","Inbound Calls","Answered Calls","Bounced Calls","Transferred Calls","Average Call Duration");
	}
	public function get_agents_objs()
	{
		return $this->build_agent_objs();
	}
}

?>
