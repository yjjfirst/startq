 <?php
 /*
	$debugging_groups=array("Test1" =>array(array("queue1","1","60","0","0","0","50","0","0","0"),
	                                        array("queue2","1","60","0","0","0","50","0","0","0"),
											array("queue3","1","60","0","0","0","50","0","0","0")),
							"Test2" =>array(array("queue1","1","60","0","0","0","50","0","0","0"),
	                                        array("queue2","1","60","0","0","0","50","0","0","0"),
											array("queue3","1","70","0","0","0","50","0","0","0")),
							"Test3" =>array(array("queue1","1","60","0","0","0","50","0","0","0"),
	                                        array("queue2","1","80","0","0","0","50","0","0","0"),
											array("queue3","1","60","0","0","0","50","0","0","0")));
											
	*/
	$debugging_groups=null;
	
    class RandChar
	{
	   function getRandChar($length){
	   $str = null;
	   $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
	   $max = strlen($strPol)-1;

	   for($i=0;$i<$length;$i++)
	   {
		$str.=$strPol[rand(0,$max)];
	   }

	   return $str;
	  }
	}
    
	class RandDigit
	{
	   function getRandDigit($length){
	   $str = null;
	   $strPol = "0123456789";
	   $max = strlen($strPol)-1;

	   for($i=0;$i<$length;$i++)
	   {
		$str.=$strPol[rand(0,$max)];
	   }
	   return $str;
	  }
	 }
	 
    function generate_queue_array(&$queue_array)
	{
	    $randDigitObj = new RandDigit();
		
		for($i=0;$i<9;$i++)
		{
			$queue_array[$i]=$randDigitObj->getRandDigit(4);
		}
	}
	
    function generate_debugging_group($queue_count)
	{
		global $debugging_groups;
		
		$randCharObj = new RandChar();
		$group_name=$randCharObj->getRandChar(16);
		$queues=array();
		for($i=0;$i<$queue_count;$i++)
		{
			$queue_items=null;
			$queue_name=$randCharObj->getRandChar(32);
			generate_queue_array($queue_items);
			$queues["$queue_name"]=$queue_items;
		}
		//print_r($queues);
		$debugging_groups["$group_name"]=$queues;
		//print_r($debugging_groups);
	}
	generate_debugging_group(2);
	generate_debugging_group(3);
	generate_debugging_group(3);
	generate_debugging_group(3);
	generate_debugging_group(5);
	//print_r($debugging_groups);
?>