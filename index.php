<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);

 include 'templates/htmlhead.php';
 include 'templates/adddateform.php';
 include 'templates/overview.php';
 include 'templates/bottom.php';

  $db = null; // close database connection
?>
