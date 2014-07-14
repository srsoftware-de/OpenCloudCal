<?php
	$strings=array(
			'dies ist ein test',
			'dies-ist-ein-test',
			'dies_ist_ein_test',
			'ein@test',
			'ein#test',
			'kein$test',
			'keintest');
	foreach ($strings as $str){
		echo $str.' => ';
		if (!preg_match('/[^A-Za-z0-9-]/', $str)){
			echo "ok";
		} else {
			echo "nok";
		}
		echo PHP_EOL;
	}
?>
