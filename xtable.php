<?php
class xtable
{
    private $tit,$arr,$fons,$sextra;
    public function __construct()
    {
        $this->tit=array();                          // strings with titles for first row 
        $this->arr=array();                          // data to show on cells
        $this->fons=array("#EEEEEE","#CCEEEE");      // background colors for odd and even rows
        $this->sextra="";                            // extra html code for table tag
    }
     
    public function extra($s)                       // add some html code for the tag table
    {
        $this->sextra=$s;
    }
    public function background($arr) {if (is_array($arr)) $this->fons=$arr; else $this->fons=array($arr,$arr);}
    public function titles($text,$style="") {$this->tit=$text; $this->sesttit=$style;}
    public function addrow($a) {$this->arr[]=$a;}
    public function addrows($arr) {$n=count($arr); for($i=0;$i<$n;$i++) $this->addrow($arr[$i]);}
    public function html()
    {
        $cfondos=$this->fons;
        $titulos="<tr>";
        $t=count($this->tit);
        for($k=0;$k<$t;$k++)
        {
            $titulos.=sprintf("<th>%s</th>",$this->tit[$k]);
        }
        $titulos.="</tr>";
         
        $celdas="";
        $n=count($this->arr);
        for($i=0;$i<$n;$i++)
        {
            $celdas.=sprintf("<tr style='background-color:%s'>",$this->fons[$i%2]);
            $linea=$this->arr[$i];
            $m=count($linea);
            for($j=0;$j<$m;$j++)
                $celdas.=sprintf("<td  %s>%s</td>","",$linea[$j]);
            $celdas.="</tr>";
        }
        return sprintf("<table cellpadding='0' cellspacing='0' border='1' %s>%s%s</table>",$this->sextra,$titulos,$celdas);
    }
    public function example()
    {
        $tit=array("Apellidos","Nombre","Telefono"); 
        $r1=array("Garcia","Ivan","888"); 
        $r2=array("Marco","Alfonso","555"); 
        $x=new xtable(); 
        $x->titles($tit);                    //take titles array
        $x->addrows(array($r1,$r2));         // take all rows at same time
        return $x->html();                   //return html code to get/show/save it 
    }
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
include_once("./parser.php");
$groups = $ini_array["groups"];
$column_eng = array("id","Calls in Queue","Longest Wait Time","Agents Available","Inbound Calls","Answered calls","Average Wait Time","Abandoned Calls","Transferred to voicemail","Outgoing calls");
$column_name=$column_eng;

function time_strformat($secs) 
{
  $hour = floor($secs/3600);
  $minute = floor(($secs-3600*$hour)/60);
  $second = floor((($secs-3600*$hour)-60*$minute)%60);
  $ret_fmt= sprintf("%02d:%02d:%02d", $hour,$minute,$second);
  
  return $ret_fmt;
}

function secs_to_strtime(&$row)
{
	for($i=0;$i<count($row);$i++)
	{
		if($i == 2 || $i == 6)
		{
			$row[$i]= time_strformat($row[$i]);
		}
	}
	return $row;
}

function data_init_group_row(&$rows)
{
	$data_raw_init_values = array("","1","00:01:00","0","0","0","00:00:00","0","0","0"); 
	$i=1;
	for($i=1;$i<count($data_raw_init_values);$i++)
	{
		if($i == 2 || $i == 6)
		{
			array_push($rows,strtotime($data_raw_init_values[$i])-strtotime("00:00:00"));
		}
		else
		{
			array_push($rows,$data_raw_init_values[$i]);
		}
	}
	//print_r($rows);
}

function conf_group_table($group,&$group_name, &$rows)
{
	$table = array();
	$group_name = key($group);
	$queue_name = current($group);
	$queue_row = explode(",",$queue_name);
    //print_r($queue_row);
	while($_name = current($queue_row)) 
    { 
	 $row = array();
     $row[0] = $_name;
	 data_init_group_row($row);
	 array_push($rows,$row);      
     next($queue_row);
    }
	$table["$group_name"]=$rows;
	//print_r($table);
	return $table;
}

function build_group_table($conf_groups,$column_name)
{
   $i=0;
   $group_table=array();
   
   while($group = current($conf_groups))
   {
	   $rows=array(count($column_name));
	
	   $group_table[$i]=conf_group_table($conf_groups, $group_name, $rows);
       $i++;	
	   next($conf_groups);
   }
   //print_r($group_table);
   return $group_table;
}

function show_group_html_table($group_tables,$column_name)
{
	foreach($group_tables as $table)
	{
       $group_name = key($table);  
	   echo $group_name."<br />";
	   
	   $total=array();
	   array_push($total,"Total");
	   
	   $rows = current($table);
	   
	   $t1=new xtable(); 
	   foreach($rows as $row)
	   {
		    if($row == 10)
			{
				//skip the obj 10;
				continue;
			}	
			
			for($j=1;$j<count($row);$j++)
			{
		 		//print_r($total);
				//echo "#### total[$j]="."$total[$j]\n";
				if($j>=count($total))
				{
					array_push($total,$row[$j]);  
				}
				else
					$total[$j]+=$row[$j];
				
			}
			$row = secs_to_strtime($row);
			$t1->addrow($row);
		}
		//print_r($total);
		secs_to_strtime($total);
		$t1->addrow($total);
		$t1->titles($column_name);
		//print_r($column_name);
		//$t1->extra(" style='width:500px; text-align:center; width:500px;background-color:gray; color:black;'");
		$t1->extra("style='padding-left:1px;padding-right:1px; text-align:center;width:500px; background-color:cyan; color:navy;'");
		echo $t1->html()."<hr />";  
    }
}

$group_tables = build_group_table($groups,$column_name);

show_group_html_table($group_tables,$column_name);

?>