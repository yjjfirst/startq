 <?php
 /*
	$debugging_groups=array("Test1" =>array("queue1"=>array("1","60","0","0","0","50","0","0","0"),
	                                        "queue2"=>array("1","60","0","0","0","50","0","0","0"),
											"queue3"=>array("1","60","0","0","0","50","0","0","0")),
							"Test2" =>array("queue1"=>array("1","60","0","0","0","50","0","0","0"),
	                                        "queue2"=>array("1","60","0","0","0","50","0","0","0"),
											"queue3"=>array("1","70","0","0","0","50","0","0","0")),
							"Test3" =>array("queue1"=>array("1","60","0","0","0","50","0","0","0"),
	                                        "queue2"=>array("1","80","0","0","0","50","0","0","0"),
											"queue3"=>array("1","60","0","0","0","50","0","0","0")));
											
	*/
class debugger
{
	function get_random_digit($length,$range)
	{
	    $str = null;
	    $strPol = "0123456789";
	    $max = strlen($strPol)-1;

	    for($i=0;$i<$length;$i++)
	    {
	        $str.=$strPol[rand(0,$max)];
	    }
	    $str = intval($str);
	    if ( $str < intval($range[0]) || $str > intval($range[1]))
        {
        	$str = $this->get_random_digit($length, $range);
        }

	    return $str;
	}
}  
?>