<?php

require 'init.php';


if (isset($_GET['clear_imported']) && $_GET['clear_imported']=='true'){
	clear_imported($db);
}

if (isset($_GET['autoimport']) && $_GET['autoimport']=='true'){
	set_time_limit(0);
	
	//Rosenkeller::read_events();
	//WagnerVerein::read_events();
	//CosmicDawn::read_events();
	Kassablanca::read_events();
	die();
	
	include 'config/autoimport.php';
	if (isset($parse_imports)){
		foreach ($parse_imports as $import){
			parserImport($import);
		}
	}
	if (isset($ical_import_urls)){
		foreach ($ical_import_urls as $item){
			if (is_array($item)){
				importIcal($item['url'],$item['tag']);
			} else importIcal($item);
		}
	}
	die();	
} else {
	include 'templates/head.php';
}

function gricalValue(){
	if (isset($_POST['gricalpost']) && $_POST['gricalpost']=='on'){
		return 'checked';
	}
}

function calciferValue(){
	if (isset($_POST['calciferpost']) && $_POST['calciferpost']=='on'){
		return 'checked';
	}
}

/* if data for a new appointment is recieved, handle it */
if (isset($_POST['newappointment'])){
	$appointment=parseAppointmentData($_POST['newappointment']); // create new appointment
	if ($appointment){
		$appointment->save(); // save appointment
	} else { // if appointment data is invalid
		unset($_POST['nextaction']); // do not add sessions or links
	}
}

/* if sessiondata is provided: create session */
if (isset($_POST['newsession'])){
	$session=parseSessionData($_POST['newsession']); // try to create session
	if ($session){ // if successfull:
		$session->save(); // save session
		$appointment=Event::load($session->aid);
	}
}

/* if linkdata is provided: create link */
if (isset($_POST['newlink'])){
	$link=parseLinkData($_POST['newlink']); // try to create link
	if ($link){ // if successfull:
		$link->save(); // save session
		$appointment=Event::load($_POST['newlink']['aid']);
		$appointment->add_link($link);
		$appointment->save();
	}
}

/* if attachment data is provided: create attachment */
if (isset($_POST['newattachment'])){
	$link=parseAttachmentData($_POST['newattachment']); // try to create link
	if ($link){ // if successfull:
		$link->save(); // save session
		$appointment=Event::load($_POST['newattachment']['aid']);
		$appointment->add_attachment($link);
		$appointment->save();
	}
}


/* if edited appointment data is provided: save! */
if (isset($_POST['editappointment'])){	
	$appointment=parseAppointmentData($_POST['editappointment']);
	if ($appointment){
		$appointment->save();
	}
	$appointment->loadRelated();
}

if (isset($_POST['icalimporturl'])){
	importIcal($_POST['icalimporturl']);
} elseif (isset($_GET['icalimporturl'])){
		importIcal($_GET['icalimporturl']);
}



/* session shall be deleted. */
if (isset($_POST['deletesession'])){
	$sid=$_POST['deletesession'];
	session::delete($sid);
}

/* attachment shall be removed from appointment */
if (isset($_POST['deleteattachment'])){
	$uid=$_POST['deleteattachment'];
	$aid=$_GET['show'];
	$appointment=Event::load($aid);
	$appointment->remove_attachment((int)$uid);
}

/* link shall be removed from appointment */
if (isset($_POST['deletelink'])){
	$uid=$_POST['deletelink'];
	$aid=$_GET['show'];
	$appointment=Event::load($aid);
	$appointment->remove_link((int)$uid);
}


if (isset($_POST['nextaction']) && $_POST['nextaction']=='addsession'){
	include 'templates/addsession.php';
	include 'templates/detail.php';

} else if (isset($_POST['nextaction']) && $_POST['nextaction']=='addattachment'){
	include 'templates/addattachment.php';
	include 'templates/detail.php';

} else if (isset($_POST['nextaction']) && $_POST['nextaction']=='addlink'){
	include 'templates/addlink.php';
	include 'templates/detail.php';

} else if (isset($_GET['show'])){
	$app_id=$_GET['show'];
	$appointment=Event::load($app_id);
	include 'templates/detail.php';

} else if (isset($_POST['clone'])) {
	if (isSpam($_POST)) die;
	$app_id=$_POST['clone'];
	$appointment=Event::load($app_id);
	unset($appointment->id);
	$appointment->save();
	foreach ($appointment->links as $link){
		$link->aid=$appointment->id;
		$appointment->add_link($url);
	}
	$selected_tags=array();
	foreach ($appointment->tags as $tag){
		$tag->aid=$appointment->id;
		$appointment->add_tag($tag);
		$selected_tags[]=$tag->text;
	}
	$appointments = Event::loadCurrent($selected_tags);
	include 'templates/editdateform.php';
	include 'templates/overview.php';

} else if (isset($_POST['edit'])) {
	if (isSpam($_POST)) die;
	$app_id=$_POST['edit'];
	$appointment=Event::load($app_id);
	include 'templates/editdateform.php';
	include 'templates/detail.php';

} else if (isset($_GET['import'])) {
	$import=$_GET['import'];
	if ($import=='ical'){
		include 'templates/icalimport.php';
	}
	$appointments = Event::loadCurrent($selected_tags);
	include 'templates/overview.php';

} else if (isset($_POST['delete'])){
	if (isSpam($_POST)) die;
	$app_id=$_POST['delete'];
	if (isset($_POST['confirm'])){
		if ($_POST['confirm']=='yes'){
			$appointment=Event::delete($app_id);
		}
		$appointments = Event::loadCurrent($selected_tags);
		include 'templates/adddateform.php';
		include 'templates/overview.php';
	} else {
		$appointment=Event::load($app_id);
		include 'templates/confirmdelete.php';
		include 'templates/detail.php';
	}

} else if (isset($_GET['past'])){
	$appointments = Event::loadAll($selected_tags);
	include 'templates/adddateform.php';
	include 'templates/overview.php';
} else {
	$appointments = Event::loadCurrent($selected_tags);
	include 'templates/adddateform.php';
	include 'templates/overview.php';
}

if (!isset($_POST['nextaction'])){
	if (isset($_POST['gricalpost']) && $_POST['gricalpost']=='on' && isset($appointment)){
		if ($appointment->sendToGrical()){
			$notification=loc('Appointment sent to #service.');
			$tags='%23'.$appointment->tags('+%23');
			$notification=str_replace('#service','<a href="https://grical.org/s/?query='.$tags.'">grical</a>',$notification);
			notify($notification);
		}
	}
	if (isset($_POST['calciferpost']) && $_POST['calciferpost']=='on' && isset($appointment)){
		if ($appointment->sendToCalcifer()){
			$notification=loc('Appointment sent to #service.');
			$notification=str_Replace('#service','<a href="https://calcifer.datenknoten.me/tags/opencloudcal">calcifer</a>',$notification);
			notify($notification);
		}
	}
}


if (($format!='ical') && isset($_SESSION['debug']) && $_SESSION['debug']=='true'){
	echo '<div class="debug"><textarea>';
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
	echo '</div>';
}

include 'templates/bottom.php';

$db = null; // close database connection
?>
