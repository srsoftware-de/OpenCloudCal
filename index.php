<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);

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
        appointment::create($new_app_data['title'],$new_app_data['description'],$start,$end,null);
        echo $start.PHP_EOL;
        echo $end.PHP_EOL;
      }
    }
  } else {
    include 'templates/adddateform.php';
    include 'templates/overview.php';
  }
  include 'templates/bottom.php';


  echo "<textarea>";
  print_r($_POST);
  echo "</textarea>";

  $db = null; // close database connection
?>
