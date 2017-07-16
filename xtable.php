<?php
require_once ('debugging_data.php');
require_once ('parser.php');
include_once ('queues.php');
date_default_timezone_set('America/Vancouver');
error_reporting(E_ERROR | E_PARSE);
class xtable
{
    public  $column_names;
    private $init_values,$time_items,$array_objs,$color_objs; 

    public function __construct()
    {
        $this->parser = parser::get_instance(); 
    }

    private function _init_row_data(&$_init_rows)
    {
        for($i=0;$i<count($this->init_values);$i++)
        {
            array_push($_init_rows,$this->init_values[$i]);
        }
    }

    private function _time_strformat($secs) 
    {
        if(!is_numeric($secs))
        {
            //var_dump($secs);
            return $secs;
        }
        $date_str = '';
        $year = date("Y",(int)$secs);

        if($year > 1970)
        {
            $date_str = date('Y-m-d H:i:s', (int)$secs);             
        }
        else
        {
            $hour = floor($secs/3600);
            $minute = floor(($secs-3600*$hour)/60);
            $second = floor((($secs-3600*$hour)-60*$minute)%60);
            $date_str= sprintf("%02d:%02d:%02d", $hour,$minute,$second);
        }
        return $date_str;
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

    public function convert_format($col, $value)
    {
        if (in_array($col, $this->time_items))
            return $this->_time_strformat($value);

        return $value;
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
    
    public function agent_default_values($table_objs=NULL)
    {
        if(!empty($table_objs))
        {
            $this->array_objs=$table_objs;
        }
        foreach($this->array_objs as $key=>$value)
        {
            $new_row=array();
            $this->_init_row_data($new_row);
            unset($this->array_objs[$key]);
            $this->array_objs[$key]=$new_row;
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

	public function group_retrive_from_asterisk()
	{
	    $groups = $this->array_objs;
        $all_queue_names = get_all_queues(); 

        foreach($groups as $group=>$tables)
        {
            foreach($tables as $queue_name=>$attrs)
            {
                if(!in_array($queue_name,$all_queue_names))
                {
                    unset($this->array_objs[$group][$queue_name]);
                    continue;
                }

                $this->array_objs[$group][$queue_name]=array_values(get_queue_status($queue_name));
            }
        }
    }

    public function group_retrive_rest_queues()
    {
       $groups = $this->array_objs;
       $all_queues_in_config = NULL;

       foreach($groups as $group=>$tables)
       {
           foreach($tables as $queue_name=>$attrs)
           {
               $all_queues_in_config[]=$queue_name;
           }
       }

       $all_queues_asterisk = get_all_queues();
       foreach($all_queues_asterisk as $queue_name) 
       {
           //if (!empty($all_queues_in_config))
           if(!in_array($queue_name,$all_queues_in_config)) 
           {
               $this->array_objs["Others"][$queue_name]=array_values(get_queue_status($queue_name)); 
           }         
       }
 
    }


    public function agent_retrive_from_asterisk()
    {
        $agents = $this->array_objs;
        $all_queues_name =  get_all_queues();

        foreach($agents as $agent_name=>$attrs)
        {
            sscanf($agent_name, "%[^-]-%[^-]",$_id, $user_name);
            if(!in_array($_id,$all_queues_name))
            {
                //unset($this->array_objs[$agent][$agent_name]);
                continue;
            }
            $agent_belongs = agent_belongs($user_name);
            if(!in_array($_id,$agent_belongs))
            {
                continue;
            }

            $this->array_objs[$agent_name]=array_values(get_agent_status($_id, $user_name));   
        }
    }	
    
    public function agent_retrive_rest_agents()
    {
        $agents = $this->array_objs;

        $all_agents = get_all_agents();
        $conf_agents = array();
        if(!is_null($agents))
        foreach($agents as $agent_name=>$attrs)
        {
            sscanf($agent_name, "%[^-]-%[^-]",$_id, $user_name);
            $conf_agents[] = $user_name;       
        }

        foreach($all_agents as $agent_name)
        {
            if(!in_array($agent_name, $conf_agents))
            {
                $agent_belongs = agent_belongs($agent_name);
                if(count($agent_belongs) > 0)
                {
                    $this->array_objs["$agent_belongs[0]-$agent_name"]=
                        array_values(get_agent_status(NULL, $agent_name));
                }
                else
                {
                  if(!is_null(get_agent_status(NULL,$agent_name)))  
                  $this->array_objs[$agent_name]=array_values(get_agent_status(NULL, $agent_name));
                }
            }
        }
    }

    public function get_color_by_range($_cloumn_index, $_value)
    {
        $ret_color = 'unset';	
        $colors = $this->color_objs;

        foreach($colors as $_index=>$color_array)
        {
            if($_index != $_cloumn_index)
            {
                continue;
            }
            
            foreach($color_array as $_color=>$_range_array)
            {
                if($_value>=$_range_array[0] && $_value <= $_range_array[1])
                {
                    $ret_color = $_color;
                }
            }
        }
        return $ret_color;
    }

    public function get_color_by_value($_cloumn_index, $_value)
    {
        $ret_color = 'unset';	
        $colors = $this->color_objs;

        if(!is_numeric($_value))
        {
            return $ret_color;
        }

        if(!array_key_exists($_cloumn_index, $colors))
        {
            return $ret_color;
        }

        foreach($colors as $_index=>$color_array)
        {
            if($_index != $_cloumn_index)
            {
                continue;
            }

            foreach($color_array as $_to_value=>$_color)
            {   
                if(strlen($color_array[$_to_value])>0 && $_to_value == $_value )
                {
                    $ret_color = $_color;
                }
            }
        }   
        return $ret_color;
    }

    public function get_agent_real_name($agent)
    {
        $agent_real_names = $this->parser->get_agent_name_options();
        foreach($agent_real_names as $key=>$name) {
            if ($key == $agent) return $name; 
        }

        return $agent;
    }

    public function get_agent_queue_name($row,$queue_str)
    {
        $agent_queue = $queue_str;
        static $agent=null;

        sscanf($queue_str, "%[^-]-%[^-]",$queue_id, $new_agent);

        $agent_queue = sprintf("%s-%s", $queue_id, $this->get_agent_real_name($new_agent));

        if(is_null($new_agent))
        { 
            return $agent_queue;
        }

        if($new_agent!=$agent)
        {
            $agent=$new_agent;
        }
        else
        {
            $agent_queue = sprintf("→%d",$queue_id);
        }

        return $agent_queue;
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

?>


