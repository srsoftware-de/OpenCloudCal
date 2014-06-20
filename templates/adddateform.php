<?php
  include 'forms.php';
?>
<form class="adddate" method="POST">
  <div class="left">
  <div id="title">
    <?php echo loc('title'); ?> <input type="text" name="newappointment[title]"/>
  </div>
  <div id="location">
    <?php echo loc('location'); ?> <input type="text" name="newappointment[location]"/>
  </div>
  <div id="coordinates">
    <?php echo loc('coordinates'); ?><input type="text" id="coords" name="newappointment[coordinates]"/>
  </div>
  <div id="description">
    <?php echo loc('description'); ?> <textarea name="newappointment[description]"></textarea>
  </div>
  <div id="tags">
    <?php echo loc('tags'); ?> <input type="text" name="newappointment[tags]"/>
  </div>
  </div>
  <div class="right">
    <?php include 'openlayers.php'; ?>
  <div class="start">
      <?php echo loc('start date'); datepicker('newappointment[start]'); echo loc('start time'); timepicker('newappointment[start]',time()); ?>
  </div>
  <div class="end">
      <?php echo loc('end date (optional)'); datepicker('newappointment[end]'); echo loc('end time'); timepicker('newappointment[end]') ?>
  </div>
  </div>
  <input type="checkbox" name="addsession"/><?php echo loc('Add a session to this appointment in the next step.'); ?>
  <?php echo '<input type="submit" value="'.loc('create new appointment').'"/><br/>'.PHP_EOL; ?>
</form>
