<?php
require_once ('debugging_data.php');
require_once ('parser.php');
include_once ('queues.php');

class xtable
{
	public  $column_names;
	private $init_values,$time_items,$array_objs,$color_objs; 

	private function _init_row_data(&$_init_rows)
	{
		for($i=0;$i<count($this->init_values);$i++)
		{
			array_push($_init_rows,$this->init_values[$i]);
		}
	}
	private function _time_strformat($secs) 
	{
	  $hour = floor($secs/3600);
	  $minute = floor(($secs-3600*$hour)/60);
	  $second = floor((($secs-3600*$hour)-60*$minute)%60);
	  $ret_fmt= sprintf("%02d:%02d:%02d", $hour,$minute,$second);
	  
	  return $ret_fmt;
	}
	private function _get_column_index($_column_name)
	{	
		for($i=0;$i<count($this->column_names);$i++)
		{
			if($this->column_names[$i] == $_column_name)
			{
				//First column is 'Queue Id'
				$i=$i - 1;
				break;
			}
		}
		if($i == count($this->column_names))
		{
			return -1;
		}
		return $i;
	}
	private function _name_in_array($_table_name,$_table_objs)
	{
		$ret = false;
		
		foreach($_table_objs as $table_name=>$tables)
		{
		    if($_table_name == $table_name)
			{
				$ret = true;
				break;
			}
		}
		return $ret;
	}
	
	///////////////////////////////////////////////////////////////////////////////////
        public function secs_to_strtime(&$rows_to_adjust)
       {
		for($i=0;$i<count($rows_to_adjust);$i++)
		{   
			if(in_array($i,$this->time_items))
			{
				$rows_to_adjust[$i]= $this->_time_strformat($rows_to_adjust[$i]);
			}
		}
	    return $rows_to_adjust;
	}
	public function set_default_values($table_objs=NULL)
	{
		if(!empty($table_objs))
		{
			$this->array_objs=$table_objs;
		}
		foreach($this->array_objs as $keys=>$values)
		{
			if(is_array($values))
			{
				foreach($values as $key=>$value)
				{
					$new_row=array();
					$this->_init_row_data($new_row);
					unset($this->array_objs[$keys][$key]);
					$this->array_objs[$keys][$key]=$new_row;
				}
			}
		}
	}
	public function set_value($group,$queue,$_column_name,$value)
	{
		if(!in_array($_column_name,$this->column_names))
		{
			return -1;
		}
		if(!$this->_name_in_array($group,$this->array_objs))
		{
			return -1;
		}
		if(!$this->_name_in_array($queue,$this->array_objs[$group]))
		{
			return -1;
		}
        
		$column_index = $this->_get_column_index($_column_name);
		if($column_index < 0)
		{
			return -1;
		}
		$this->array_objs[$group][$queue][$column_index]=$value;
		return 0;
	}
	public function retrive_from_asterisk()
	{
		$debugger = new debugger();
		$groups = $this->array_objs;
		
                foreach($groups as $group=>$tables)
		{
			foreach($tables as $queue=>$queues)
			{
				//skip the 'Queue Id' from column_names
				for($i=0;$i<count($this->column_names)-1;$i++)
				{
					if($i == 0)
					{
						$this->array_objs[$group][$queue][$i]=
							$debugger->get_random_digit(1,array(0,4));
					}
					else 
					{
						$this->array_objs[$group][$queue][$i]=
							$debugger->get_random_digit(3,array(100,150));
					}
				}
			}
		}
	}
	public function group_retrive_from_asterisk()
	{
	    $groups = $this->array_objs;

	    foreach($groups as $group=>$tables)
	    {
		    foreach($tables as $queue_name=>$attrs)
		    {
                           $this->array_objs[$group][$queue_name]=array_values(get_queue_status($queue_name));
		    }
	    }
	}
        public function agent_retrive_from_asterisk()
	{
	    $agents = $this->array_objs;
            foreach($agents as $agent=>$tables)
            {
	        foreach($tables as $agent_name=>$attrs)
		{
			sscanf($agent_name, "%[^-]-%[^-]",$user_name, $_id);
                        $this->array_objs[$agents][$agent_name]=array_values(get_agent_status($_id));
		}
	    }

	}	

	public function get_color_by_range($_cloumn_index, $_value)
	{
		$ret_color = 'unset';
		
		$colors = $this->color_objs;

		if(!is_integer($_value))
		{
			return $ret_color;
		}

		foreach($colors as $_index=>$color_array)
		{
			if($_index == $_cloumn_index)
			{
				foreach($color_array as $_color=>$_range_array)
				{
					if($_value>=$_range_array[0] && $_value <= $_range_array[1])
					{
						$ret_color = $_color;
					}
				}
			}
		}
		return $ret_color;
	}
    public function get_color_by_value($_cloumn_index, $_value)
    {
    	$ret_color = 'unset';
		
		$colors = $this->color_objs;
		
		//var_dump($_value);

		if(!is_integer($_value))
		{
			return $ret_color;
		}

		foreach($colors as $_index=>$color_array)
		{
			if($_index == $_cloumn_index)
			{
				foreach($color_array as $_to_value=>$_color)
				{
					if($_value == $_to_value)
					{
						$ret_color = $_color;
					}
				}
			}
		}
		return $ret_color;
    }

	public function get_agent_queue_name($row,$queue_str)
	{
		$queue_id = $queue_str;
		static $agent=null;
		
		sscanf($queue_str, "%[^-]-%[^-]",$new_agent, $_id);
		if($new_agent !=$agent)
		{
			$agent=$new_agent;
		}
		else
		{
			$queue_id = sprintf("→%d",$_id);
		}
	
		return $queue_id;
	}
	
	////////////////////////////////////////////////////////////////////////////////
	public function set_init_values($_values)
	{
		$this->init_values=$_values;
	}
	public function set_time_items($_time_items)
	{
		$this->time_items=$_time_items;
	}
	public function set_column_names($_names)
	{
		$this->column_names=$_names;
	}
	public function set_array_objs($_array_objs)
	{
		$this->array_objs=$_array_objs;
	}
	public function get_array_objs()
	{
		return $this->array_objs;
	}
	public function set_color_objs($_color_objs)
	{
		$this->color_objs = $_color_objs;
	}
	//////////////////////////////////////////////////////////////////////////////////
}

$parser = parser::get_instance();
$group_table = new xtable();

$group_table->set_init_values($parser->get_group_init_values());
$group_table->set_time_items($parser->get_group_time_items());
$group_table->set_column_names($parser->get_group_cloumn_names());
$group_table->set_array_objs($parser->get_groups_objs());
$group_table->set_color_objs($parser->get_group_collors());
$group_table->set_default_values();
//$group_table->retrive_from_asterisk();
$group_table->group_retrive_from_asterisk();
///////////////////////////////////////////////////////////////////////////////
$groups=$group_table->get_array_objs();



$parser = parser::get_instance();
$agent_table = new xtable();

$agent_table->set_init_values($parser->get_agent_init_values());
$agent_table->set_time_items($parser->get_agent_time_items());
$agent_table->set_column_names($parser->get_agent_cloumn_names());
$agent_table->set_array_objs($parser->get_agents_objs());
$agent_table->set_color_objs($parser->get_agent_colors());
$agent_table->set_default_values();
$agent_table->retrive_from_asterisk();
/////////////////////////////////////////////
//$agents=$agent_table->agent_retrive_from_asterisk();

//print_r($agents);
?>


