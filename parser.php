<?php
require_once('queues.php');




class parser
{	
    public $ini_array,$group_objs,$agent_objs;

    private static $_instance=null;
    public  $state_index=array("available"=>1,"unavailable"=>2,"busy"=>3,"hold"=>8,"paused"=>100,"not_login"=>7,);

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

    private function build_color_range_objs($column_color)
    {
        foreach($column_color as $i=>$color_range)
        {
            $range_obj = explode(",",$color_range);
            unset($column_color[$i]);
            foreach($range_obj as $j=>$color_range)
            {
                $min=-1;
                $max=-1;
                $color='unset';

                unset($range_obj[$j]);
                sscanf($color_range, "[%d-%d]%s",$min, $max, $color);
                $range_obj[$color]=array($min,$max);
            }
            $column_color[$i]=$range_obj;
        }
        return $column_color;
    }
    ///////////////////////////////////////////////////////////////////////////////////
    public function get_group_init_values()
    {
        return array("0","0","0","0","0","0","0","0");
    }
    public function get_group_time_items()
    {
        return array();
    }
    public function get_group_cloumn_names()
    {
        $language = $this->ini_array['asterisk'];
        $language = $language['language'];
        if ($language == 'en') {
            return array("Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail");
        } else {
            return array("Appels en attente", "Temps d'attente le plus lon", "Agents disponible", "Appels entrants", "Appels repondus", "Temps d'attente moyen", "Appels abandonn", "Transfer boite vocale");
        }
    }
    public function get_groups_objs()
    {
        return $this->build_group_objs();
    }
    public function get_group_collors()
    {
        return $this->build_color_range_objs($this->ini_array["queue-colors"]);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////
    private function parse_to_agentobj(&$data_source)
    {	
	$new_row = null;
        foreach($data_source as $agent=>$queues)
        {
            $queue_row = explode(",",$queues);
            $all_agents_name = get_all_agents(); 

            if(!in_array($agent,$all_agents_name))
            {
                unset($data_source[$agent]);
                continue;
            }

            foreach($queue_row as $index=>$queue)
            {
                $queue_name = $queue."-".$agent;
                //echo "queue_name = $queue_name\n";
                $new_row[$queue_name]=array();
            }
            unset($data_source[$agent]);
        }
        $data_source = $new_row;
    }
    private function build_state_color_objs($colors)
    {
        $agent_state_color[0] = $colors;
        return $agent_state_color;
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
        return array(strval(AGENT_NOT_LOGIN),"&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;");
    }
    public function get_agent_time_items()
    {
        return array(1,2);
    }
    public function get_agent_cloumn_names()
    {
        $language = $this->ini_array['asterisk'];
        $language = $language['language'];
        if ($language == 'en') {
            return array("Agents","Agent State","Start Time","Duration","Inbound Calls","Outgoing Calls","Answered Calls","Bounced Calls","Transferred Calls","Average Call Duration");
        } else {
            return array("Agents","Statut Agents","Debut Queue","Temps Queue","Appels entrants","Appels sortants","Appels repondus","Appels Abandonn","Appels transfer","Durer moyenne des appels");
        }
    }
    public function get_agents_objs()
    {
        return $this->build_agent_objs();
    }
    public function get_agent_state_colors()
    {

       $colors=$this->ini_array["agent-colors"];
       $color_state = NULL;
    
       foreach($colors as $index=>$color_range_value)
       {
           if(!isset($this->state_index["$index"]))
           {
                continue;
           }
           $color_state[$this->state_index[$index]]=$color_range_value;
       }
       return $this->build_state_color_objs($color_state);
    }

    public function get_agent_column_colors()
    {
        $colors=$this->ini_array["agent-colors"];
        $color_range = NULL;

        foreach($colors as $index=>$color_range_value)
        {
            if(isset($this->state_index["$index"]))
            {
                continue;
            }
            $color_range[$index]=$color_range_value;
        }

        return $this->build_color_range_objs($color_range);
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
        $state_str[AGENT_HOLD]='Hold';
        $state_str[AGENT_AVAILABLE]='Available';
        $state_str[AGENT_BUSY]='Busy';
        $state_str[AGENT_PAUSED]='Paused';
        $state_str[AGENT_UNAVAILABLE]= 'Unavailable';
        $state_str[AGENT_NOT_LOGIN]='Not logged in Queue';

        $ret_str = 'UNKNOWN';

        if(!is_numeric($index)|| is_null($state_str[$index]))
        {
            return $ret_str;
        }
        return $state_str[$index];
    }
    ///////////////////////////////////////////////////////////////////////////////////
    public function get_asterisk_options()
    {
        return $this->ini_array['asterisk'];
    }
    //////////////////////////////////////////////////////////////////////////////////

    public function get_vm_options()
    {
        return $this->ini_array['vm'];
    }

    public function get_agent_name_options()
    {
        return $this->ini_array['agent-name'];
    }
    
    public function get_color_codes($color)
    {
        return $this->ini_array['color-codes'][$color];
    }
}
//print_r (parser::get_instance()->ini_array);
?>
