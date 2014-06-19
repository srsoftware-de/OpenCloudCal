<?php

  require 'init.php';

  $selected_tags = array();

  $app=new appointment(0,"this is a test appointment","2014-06-19 00:00:00", "2014-06-19 00:00:00", "50.8542,12.0586");
  $app->addTag('Srsoftware');
  $app->addUrl('http://srsoftware.de','Homepage of the developer');
  $app->addTag('OpenSource');
  $app->addUrl('http://example.com');
  print_r(appointment::loadAll());
  $db = null; // close database connection
?>
