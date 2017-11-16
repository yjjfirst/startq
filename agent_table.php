<?php
include_once './parser.php';
include_once './xtable.php';

/////////////////////////////////////////////
$parser = parser::get_instance();
$agent_table = new xtable();
$agent_table->set_init_values($parser->get_agent_init_values());
$agent_table->set_time_items($parser->get_agent_time_items());
$agent_table->set_column_names($parser->get_agent_cloumn_names());
if(!is_null($parser->get_agents_objs())){
$agent_table->set_array_objs($parser->get_agents_objs());
$agent_table->agent_default_values();
$agent_table->agent_retrive_from_asterisk();
}
$agent_table->set_color_objs($parser->get_agent_state_colors());
$agent_table->agent_retrive_rest_agents();
/////////////////////////////////////////////
$agents=$agent_table->get_array_objs();
?>
<!-- Table goes in the document BODY -->
        <table class="imagetable">
            <tr>
<?php
    foreach($agent_table->column_names as $title)
    {
?>
                    <th><?php echo $title?></th>
<?php
    }
?>
            </tr>
<?php
    $rows = 0;
    $td_class = "class=\"odd\"";
    for($j=0;$j<count($agents);$j++,next($agents))
    {
        $rows++;
        if($rows%2 == 0)
        {	 
            $td_class="class=\"even\"";
        }
        else
        {
            $td_class="class=\"odd\"";
        }
        $item_name=key($agents);
        if(count($agents[$item_name]) == 0)
        {
            continue;
        }

        $agent_name = $agent_table->get_agent_queue_name($rows-1,$item_name);
?>
                <tr>
                    <td <?php echo $td_class?>><?php echo $agent_name?></td>
<?php
        $number=$agents["$item_name"];
        $number = $agent_table->secs_to_strtime($number);
        $td_class_org = $td_class;
        foreach($number as $_index=>$item)
        {
            if($_index >= count($agent_table->column_names)-1)
            {
                continue;
            }
            if($_index == 0)
            {
                $agent_table->set_color_objs($parser->get_agent_state_colors());
                $td_color=$agent_table->get_color_by_value($_index,$item);
            }
            else 
            {
                $agent_table->set_color_objs($parser->get_agent_column_colors());                
                $td_color=$agent_table->get_color_by_range($_index + 2,$item);
            }
            if($td_color != 'unset')
            {
                $td_class=sprintf("style=\"background:%s\"",$parser->get_color_codes($td_color));
            }
            else
            {
                $td_class = $td_class_org;
            }
            if($_index == 0)
            {
                $__value = $parser->get_agent_state_str($number[0]);
            }
            else if ($_index == 1) 
            {
                if ($number[0] != AGENT_NOT_LOGIN)
                    $__value = "Logged in at " . $item;
                else
                    $__value = "Logged out at " . $item;
            }
            else if ($_index == 2)
            {
                if ($number[0] != AGENT_NOT_LOGIN)
                    $__value = "Logged in for " . $item . " hours";
                else
                    $__value = "Logoed out for " . $item . " hours";
            }
            else if ($_index == 8) 
            {
                $__value = $agent_table->second_2_string($item);
            }
            else
            {
                $__value = $item;
            }


?>
                        <td <?php echo $td_class?>><?php echo $__value?></td>
<?php
        }
?>
                </tr>
<?php
    }
?>
        </table>
