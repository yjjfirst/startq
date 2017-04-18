<?php
require_once('queues.php');

function parse_ini_file_multi($file, $process_sections = false, $scanner_mode = INI_SCANNER_NORMAL) {
    $explode_str = '.';
    $escape_char = "'";
    //load ini file the normal way
    $data = parse_ini_file($file, $process_sections, $scanner_mode);
    if (!$process_sections) {
        $data = array($data);
    }
    foreach ($data as $section_key => $section) {
        // loop inside the section
        foreach ($section as $key => $value) {
            if (strpos($key, $explode_str)) {
                if (substr($key, 0, 1) !== $escape_char) {
                    // key has a dot. Explode on it, then parse each subkeys
                    // and set value at the right place thanks to references
                    $sub_keys = explode($explode_str, $key);
                    $subs =& $data[$section_key];
                    foreach ($sub_keys as $sub_key) {
                        if (!isset($subs[$sub_key])) {
                            $subs[$sub_key] = '';
                        }
                        $subs =& $subs[$sub_key];
                    }
                    // set the value at the right place
                    $subs = $value;
                    // unset the dotted key, we don't need it anymore
                    unset($data[$section_key][$key]);
                }
                // we have escaped the key, so we keep dots as they are
                else {
                    $new_key = trim($key, $escape_char);
                    $data[$section_key][$new_key] = $value;
                    unset($data[$section_key][$key]);
                }
            }
        }
    }
    if (!$process_sections) {
        $data = $data[0];
    }
    return $data;
}



class parser
{	
    public $ini_array,$group_objs,$agent_objs;

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
            self::$_instance->ini_array = parse_ini_file_multi($conf_path, true);
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
        return $this->build_color_range_objs($this->ini_array["queue-colors"]);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////
    private function parse_to_agentobj(&$data_source)
    {	
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
        return array(1,2,8);
    }
    public function get_agent_cloumn_names()
    {
        return array("Agents","ACD STATE","ACD State Start Time","ACD State Duration","Inbound Calls","Outgoing Calls","Answered Calls","Bounced Calls","Transferred Calls","Average Call Duration");
    }
    public function get_agents_objs()
    {
        return $this->build_agent_objs();
    }
    public function get_agent_state_colors()
    {
        return $this->build_state_color_objs($this->ini_array["agent-colors"]["state"]);
    }

    public function get_agent_column_colors()
    {
        $colors=$this->ini_array["agent-colors"];
        $color_range = NULL;

        foreach($colors as $index=>$color_range_value)
        {
            if($index == 'state')
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

    public function get_color_codes($color)
    {
        return $this->ini_array['color-codes'][$color];
    }
}
//print_r (parser::get_instance()->ini_array);
?>
