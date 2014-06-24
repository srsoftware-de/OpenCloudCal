<?php if ($format=='html') { ?>
<form class="confirmation" action="." method="POST">
	<?php echo str_replace('%apptitle', $appointment->title, loc('Seriously, delete "%apptitle"?'));?><br/>
  <input type="hidden" name="delete" value="<?php echo $appointment->id; ?>" />
  <button type="submit" class="delete" name="confirm" value="yes"><?php echo loc('Yes'); ?></button>&nbsp;&nbsp;&nbsp;
  <button type="submit" name="confirm" value="no"><?php echo loc('No'); ?></button>
</form>
<?php } ?>
