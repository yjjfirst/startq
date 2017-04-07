<?php
include_once './parser.php';
include_once './xtable.php';

/////////////////////////////////////////////
$parser = parser::get_instance();
$agent_table = new xtable();

$agent_table->set_init_values($parser->get_agent_init_values());
$agent_table->set_time_items($parser->get_agent_time_items());
$agent_table->set_column_names($parser->get_agent_cloumn_names());
$agent_table->set_array_objs($parser->get_agents_objs());
$agent_table->set_color_objs($parser->get_agent_colors());
$agent_table->set_default_values();
//$agent_table->retrive_from_asterisk();
$agent_table->agent_retrive_from_asterisk();
$agent_table->agent_retrive_agents();
/////////////////////////////////////////////
$agents=$agent_table->get_array_objs();
for($i=0;$i<count($agents);next($agents),$i++)
{
    //print_r($agents
    $agent_name = key($agents);
    $agent = current($agents);
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
    for($j=0;$j<count($agent);$j++,next($agent))
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
        $item_name=key($agent);
        if(count($agent[$item_name]) == 0)
        {
            continue;
        }

        $agent_name=$agent_table->get_agent_queue_name($rows-1,$item_name);
?>
                <tr>
                    <td <?php echo $td_class?>><?php echo $agent_name?></td>
<?php
        $number=$agent["$item_name"];
        $number = $agent_table->secs_to_strtime($number);
        $td_class_org = $td_class;
        foreach($number as $_index=>$item)
        {
            if($_index >= count($agent_table->column_names)-1)
            {
                continue;
            }

            $td_color=$agent_table->get_color_by_value($_index,$item);
            if($td_color != 'unset')
            {
                $td_class=sprintf("class=\"%s\"",$td_color);
            }
            else
            {
                $td_class = $td_class_org;
            }
            if($_index == 0)
            {
                $__value = $parser->get_agent_state_str($number[0]);
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
<?php
}
?>
