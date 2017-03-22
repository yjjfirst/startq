<?php
include_once("./parser.php");

class xtable
{
    //private $tit,$arr,$fons,$sextra;
    public $row_init_array, $data_source,$cloumn_names,$init_values,$time_items; 
	
	private $table_name;
	
	
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
    private function _fill_table($name, $row_names_str)
	{
		$new_table = array();
		$this->table_name = $name;
		$row_to_add = explode(",",$row_names_str);
		while($_name = current($row_to_add)) 
		{ 
		 $new_row = array();
		 $this->_init_row_data($new_row);
		 $new_table["$_name"]=$new_row;      
		 next($row_to_add);
		}
		unset($this->data_source["$this->table_name"]);
		$this->data_source["$this->table_name"]=$new_table;
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
	
	public function build_table()
	{
	   $i=0;
	   foreach($this->data_source as $key=>$value)
	   {
		   $this->_fill_table($key,$value);
		   $i++;	
	   }
	   return $this->data_source;
	}
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////


?>