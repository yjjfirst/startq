<?php
include_once './parser.php';
include_once './xtable.php';

//////////////////////////////////////////////////////////////////////////////
$parser = parser::get_instance();
$group_table = new xtable();

$group_table->set_init_values($parser->get_group_init_values());
$group_table->set_time_items($parser->get_group_time_items());
$group_table->set_column_names($parser->get_group_cloumn_names());
$group_table->set_array_objs($parser->get_groups_objs());
$group_table->set_color_objs($parser->get_group_collors());
$group_table->set_default_values();
$group_table->group_retrive_from_asterisk();
$group_table->group_retrive_rest_queues();
/////////////////////////////////////////////////////////////////////////////
$groups=$group_table->get_array_objs();
define("GROUP_LONGEST",'1');
define ("GROUP_AVERAGE",'5');
$group_longest = 0;
$group_average = 0;
?>
<!-- Table goes in the document BODY -->
        <table class="imagetable">
            <tr>
                <th><?php echo ''?></th>
<?php
foreach($group_table->column_names as $title)
{
?>
                <th><?php echo $title?></th>
<?php
}
?>
            </tr>
<?php
for($i=0;$i<count($groups);next($groups),$i++)
{
    //print_r($groups
    $group_name = key($groups);
    if(count($groups[$group_name]) == 0)
    {
        continue;
    }

    $total_items=array();
    $queues = current($groups);
    $rows = 0;
    $td_class = "class=\"odd\"";
?>
            <tr>
            <td><?php echo $group_name?></td>
            </tr>
<?php

    for($k=0;$k<count($queues);$k++,next($queues))
    {
        $rows++;
        $queue_name=key($queues);
        if($rows%2 == 0)
        {	 
            $td_class="class=\"even\"";
        }
        else
        {
            $td_class="class=\"odd\"";
        }
?>
                    <tr>
                        <td <?php echo $td_class?>><?php echo $queue_name?></td>
<?php
        $queue=$queues["$queue_name"];
        for($j=0;$j<count($queue);$j++)
        {
            if($j>=count($total_items))
            {
                array_push($total_items,$queue[$j]);  
            }
            else
                $total_items[$j]+=$queue[$j];
        }
        if($group_longest < $queue[GROUP_LONGEST] )
        {
            $group_longest = $queue[GROUP_LONGEST];
            printf("%s\n",$group_longest);
        }

        $queue = $group_table->secs_to_strtime($queue);
        $td_class_org = $td_class;
        foreach($queue as $_index=>$item)
        {
            $td_color=$group_table->get_color_by_range($_index,$item);
            if($td_color != 'unset')
            {
                $td_class=sprintf("class=\"%s\"",$td_color);
            }
            else
            {
                $td_class = $td_class_org;
            }
            ?>
                            <td <?php echo $td_class?>><?php echo $item?></td>
<?php
        }
?>
                    </tr>
<?php
    }
    $total_items[GROUP_LONGEST] = $group_longest;
    $total_items[GROUP_AVERAGE] = round((int)$total_items[GROUP_AVERAGE]/$rows);
    $total_items = $group_table->secs_to_strtime($total_items);

    $rows++;
    if($rows%2 == 0)
    {	 
        $td_class="class=\"even\"";
    }
    else
    {
        $td_class="class=\"odd\"";
    }
?>
                <tr>
                <td <?php echo $td_class?>><?php echo $group_name." Total"?></td>
<?php
    foreach($total_items as $item)
    {
?>
                    <td <?php echo $td_class?>><?php echo $item?></td>
<?php
    }
?>
                </tr>
<?php
}
?>
        </table>
