<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);
  print_r($appointments);
  
  $app1=$appointments[0];
  print_r($app1);
  $app1->addTag('mops');

  $db = null; // close database connection
?>
