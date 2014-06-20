<?php
  include 'forms.php';
?>
<form class="adddate" method="POST">
  <div class="titledescription">
    <?php echo loc('title'); ?> <input type="text" name="title"/><br/>
    <?php echo loc('description'); ?> <textarea name='description'></textarea><br/>
    <?php echo loc('tags'); ?> <input type="text" name="tags"/><br/>
  </div>
  <div class="datepickers">
    <div class="start">
      <?php echo loc('start date'); datepicker('start'); echo loc('start time'); timepicker('start',time()); ?>
    </div>
    <div class="end">
      <?php echo loc('end date (optional)'); datepicker('end'); echo loc('end time'); timepicker('end') ?>
    </div>
  </div>
  <input type="checkbox" name="addsession"/><?php echo loc('Add a session to this appointment in the next step.'); ?>
  <input type="submit" value="Neuen Termin anlegen"/><br/>
</form>
