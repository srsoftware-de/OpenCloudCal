<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);

  include 'templates/htmlhead.php';

  if (isset($_POST['action'])){
    $action = $_POST['action'];
  } else {
    $action = null;
  }

  if ($action == 'adddate'){
    $start = parseDate($_POST,'start');
    if (!$start){
      warn('invalid data given for start date!');
    } else {
      $end = parseDate($_POST,'end');
      if (!$end){
        warn('invalid data given for end date!');
      } else {
        $start+=parseTime($_POST,'start');
        $end+=parseTime($_POST,'end');
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
