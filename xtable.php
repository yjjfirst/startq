<?php
include_once("./parser.php");

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
	public function set_defaults($table_objs=NULL)
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
}
?>


