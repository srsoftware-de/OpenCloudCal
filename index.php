<?php

require 'init.php';

$selected_tags = array();

include 'templates/htmlhead.php';

function parseAppointmentData($data){
	global $db_time_format;
	if (empty($data['title'])){
		warn('no title given');
		return false;
	}
	$start=parseDate($data['start']);
	if (!$start){
		warn('invalid start date');
		return false;
	}
	$end=parseDate($data['end']);
	if (!$end){
		warn('invalid end date');
		return false;
	}	
	$start+=parseTime($data['start']);
	$end+=parseTime($data['end']);
	if ($end<$start){
		$end=$start;
	}
	$start=date($db_time_format,$start);
	$end=date($db_time_format,$end);
	$app=appointment::create($data['title'],$data['description'],$start,$end,$data['location'],$data['coordinates'],false);
	if (isset($data['id'])){
		$app->id=$data['id'];
	}			
	return $app;
}



if (isset($_POST['newappointment'])){
	$app=parseAppointmentData($_POST['newappointment']);
	if ($app){
		$app->save();
		$tags=explode(' ',$_POST['newappointment']['tags']);
		foreach ($tags as $tag){
			$app->addTag($tag);
		}		
	}
}

if (isset($_POST['editappointment'])){
	$app=parseAppointmentData($_POST['editappointment']);
	if ($app){
		$app->save();
	}
}

if (isset($_GET['tag'])){
	$selected_tags[]=$_GET['tag'];
}

if (isset($_GET['show'])){
	$app_id=$_GET['show'];
	$appointment=appointment::load($app_id);
	include 'templates/detail.php';
} else if (isset($_GET['edit'])) {
	$app_id=$_GET['edit'];
	$appointments = appointment::loadAll($selected_tags);
	$appointment=$appointments[$app_id];
	include 'templates/editdateform.php';
	include 'templates/overview.php';
} else {
	$appointments = appointment::loadAll($selected_tags);
	include 'templates/adddateform.php';
	include 'templates/overview.php';
}

if (isset($_GET['debug']) && $_GET['debug']=='true'){
	echo "<textarea>";
	print_r($_POST);
	echo "</textarea>";
	if (isset($appointments)){
		echo "<textarea>";
		print_r($appointments);
		echo "</textarea>";
	}
}

include 'templates/bottom.php';

$db = null; // close database connection
?>
