<?php
include_once './debugging_data.php';
include_once './parser.php';

class xtable
{
	public  $column_names;
	private $init_values,$time_items,$array_objs; 

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
	public function set_value($group,$queue,$_column,$value)
	{
		if(!in_array($_column,$this->column_names))
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
        
		$column_index = $this->_get_column_index($_column);
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
				//skip the id column
				for($i=0;$i<count($this->column_names)-1;$i++)
				{
					$this->array_objs[$group][$queue][$i]=$debugger->get_random_digit(3);
				}
			}
		}
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
	//////////////////////////////////////////////////////////////////////////////////
}
?>


