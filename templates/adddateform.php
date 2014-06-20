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
    <?php echo loc('start date'); datepicker('start'); timepicker('start'); ?>
    <?php echo loc('end date'); datepicker('end'); timepicker('end') ?>
  </div>
  <input type="submit" value="Neuen Termin anlegen"/><br/>
</form>
