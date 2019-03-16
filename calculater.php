<?php

class Calculator{
	//this function is user in all tasks
	function add_Values($values=''){	
		$params = explode(',',$values); //This function is used to get all parameters
		//print_r($params);		
		$sum = "";
		/*This loop is add only integer values*/
		foreach ($params as $key => $value) {
			if ( (int)$value == $value && (int)$value >= 0 ) {
			    $sum = $sum + $value;	
			} else {
			    echo 'Negative numbers not allowed.';
			    exit();			   
			}					 		
		}
		echo $sum;
	}
}

//Please Enter the value in task 1
//$data = '0';
//$data = '1'; 
$data1 = '2,3';
$task1 = new calculator;
$val1 = str_replace(array(';','n'),array(',',','),preg_replace('/\\\\/', '',$data1 ));
$task1->add_Values($val1);
echo  "<br>";

//Please Enter the value in task 2
//$data2 = '4,5,6';
$data2 = '4,7,3,4,7,3,5,6,7,4,3,2,5,7,5,3,4,6,7,8,9,5,5,5,4,3,2';
$task2 = new calculator;
$val2 = str_replace(array(';','n'),array(',',','),preg_replace('/\\\\/', '',$data2 ));
$task2->add_Values($val2);
echo  "<br>";


//Please Enter the value in task 3
$data3 = '2\n3,4';
$task3 = new calculator;
$val3 = str_replace(array(';','n'),array(',',','),preg_replace('/\\\\/', '',$data3 ));
$task3->add_Values($val3);
echo  "<br>";

//Please Enter the value in task 4
$data4 = '\\;\\3;4;5';
$task4 = new calculator;
$val4 = str_replace(array(';','n'),array(',',','),preg_replace('/\\\\/', '',$data4 ));
$task4->add_Values($val4);
echo  "<br>";

//Please Enter the value in task 5
$data5 = '\\,\\2,7,-3,5,2,';
$task5 = new calculator;
$val5 = str_replace(array(';','n'),array(',',','),preg_replace('/\\\\/', '',$data5 ));
$task5->add_Values($val5);
