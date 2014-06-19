<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);
  
  $app1=$appointments[0];
  $app1->addTag('mund');
  print_r($app1);

  $db = null; // close database connection
?>
