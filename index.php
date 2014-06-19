<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);
  
  $app1=$appointments[0];
  $app1->addUrl('https://example.com','test url 2');
  print_r($app1);

  $db = null; // close database connection
?>
