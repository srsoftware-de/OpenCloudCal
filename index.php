<?php

  require 'init.php';

  $selected_tags = array();

  include 'templates/htmlhead.php';

  if (isset($_POST['newappointment'])){
    $new_app_data=$_POST['newappointment'];
    $start=parseDate($new_app_data['start']);
    if (!$start){
      warn('invalid start date');
    } else {    
      $end=parseDate($new_app_data['end']);
      if (!$end){
        warn('invalid end date');
      } else {
        $start+=parseTime($new_app_data['start']);
        $end+=parseTime($new_app_data['end']);
        if ($end<$start){
          $end=$start;
        }        
        $app=appointment::create($new_app_data['title'],$new_app_data['description'],$start,$end,$new_app_data['location'],$new_app_data['coordinates']);
        $tags=explode(' ',$new_app_data['tags']);
        foreach ($tags as $tag){
          $app->addTag($tag);
        }
      }
    }
  }
  
  if (isset($_GET['tag'])){
  	$selected_tags[]=$_GET['tag'];
  }
  
  if (isset($_GET['show'])){
  	$app_id=$_GET['show'];
  	$appointment=appointment::load($app_id);
  	include 'templates/detail.php';
  } else {
  	$appointments = appointment::loadAll($selected_tags);
  	include 'templates/adddateform.php';
  	include 'templates/overview.php';
  }

  if (isset($_GET['debug']) && $_GET['debug']=='true'){
    echo "<textarea>";
    print_r($_POST);
    echo "</textarea>";
  }

  include 'templates/bottom.php';

  $db = null; // close database connection
?>
