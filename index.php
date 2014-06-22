<?php

require 'init.php';

$selected_tags = array();

include 'templates/htmlhead.php';

/* if data for a new appointment is recieved, handle it */
if (isset($_POST['newappointment'])){
	$appointment=parseAppointmentData($_POST['newappointment']); // create new appointment
	if ($appointment){
		$appointment->save(); // save new appointment
		$tags=explode(' ',$_POST['newappointment']['tags']);
		foreach ($tags as $tag){
			$appointment->addTag($tag); // add tags
		}		
	} else { // if appointment data is invalid
		unset($_POST['addsession']); // do not add sessions
		unset($_POST['addlink']); // do not add links
	}	
}

/* if sessiondata is provided: create session */
if (isset($_POST['newsession'])){
	$session=parseSessionData($_POST['newsession']); // try to create session
	if ($session){ // if successfull:
		$session->save(); // save session
		$appointment=appointment::load($session->aid);		
	}
}

if (isset($_POST['editappointment'])){
	$appointment=parseAppointmentData($_POST['editappointment']);
	if ($appointment){
		$appointment->save();
	}
	$appointment->loadRelated();
}

if (isset($_GET['tag'])){
	$selected_tags[]=$_GET['tag'];
}

if (isset($_GET['deletesession'])){
	$sid=$_GET['deletesession'];
	session::delete($sid);
}

if (isset($_POST['addsession'])){
	include 'templates/addsession.php';
	include 'templates/detail.php';	
	
} else if (isset($_GET['show'])){
	$app_id=$_GET['show'];
	$appointment=appointment::load($app_id);
	include 'templates/detail.php';
	
} else if (isset($_GET['edit'])) {
	$app_id=$_GET['edit'];
	$appointments = appointment::loadAll($selected_tags);
	$appointment=$appointments[$app_id];
	include 'templates/editdateform.php';
	include 'templates/overview.php';
	
} else if (isset($_GET['delete'])){
	$app_id=$_GET['delete'];
	if (isset($_GET['confirm']) && $_GET['confirm']=='yes'){
		$appointment=appointment::delete($app_id);	
		$appointments = appointment::loadAll($selected_tags);
		include 'templates/adddateform.php';
	} else {
		$appointments = appointment::loadAll($selected_tags);
		$appointment=$appointments[$app_id];
		include 'templates/confirmdelete.php';
		include 'templates/detail.php';
	}
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
	if (isset($appointment)){
		echo "<textarea>";
		print_r($appointment);
		echo "</textarea>";
	}
}

include 'templates/bottom.php';

$db = null; // close database connection
?>
