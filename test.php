<?php

  require 'init.php';

  echo "\n/************** creation tests *********/\n";

  $tag1=tag::create('Tag 1');
  print_r($tag1);

  $url1=url::create('http://example.com/url/without/comment');
  $url2=url::create('http://example.com/commentedurl','this is a comment');
  $url3=url::create('http://example.com/second/url/without/comment');
  $url4=url::create('http://example.com/second/commentedurl/','another comment');
  print_r($url1);
  print_r($url2);
  print_r($url3);
  print_r($url4);

  $session1=session::create('first session','2014-06-19 00:00:00','2014-06-19 00:00:00');
  print_r($session1);

  $app=new appointment(0,"this is a test appointment","2014-06-19 00:00:00", "2014-06-19 00:00:00", "50.8542,12.0586");
  print_r($app);

  echo "\n/************* addition tests **************/\n";

  $app->addTag($tag1);
  $app->addTag('3');
  $app->addTag('SRSoftware');

  $app->addUrl($url1);
  $app->addUrl($url2);
  $app->addUrl($url3,'overwritten comment 1');
  $app->addUrl($url4,'overwritten comment 2');
  $app->addUrl('http://example.com/test1');
  $app->addUrl('http://example.com/test2','another test description');
  
  $app->addSession($session1);
  $app->addSession("second session","2014-06-19 00:00:00","2014-06-19 00:00:10" );

  print_r(appointment::loadAll());

  echo "\n/************* remove tests *******************/\n";
  $app->removeTag($tag1); 
  $app->removeTag(3);
  $app->removeTag('3');

  print_r(appointment::loadAll());

  $db = null; // close database connection
?>
