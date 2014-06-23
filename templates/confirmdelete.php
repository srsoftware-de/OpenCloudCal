<?php if ($format=='html') { ?>
<div class="confirmation">
<?php echo str_replace('%apptitle', $appointment->title, loc('Seriously, delete "%apptitle"?'));?><br/>
<a class="button red" href="?delete='<?php echo $appointment->id; ?>&confirm=yes"><?php echo loc('Yes'); ?></a>&nbsp;&nbsp;&nbsp;
<a class="button green" href="."><?php echo loc('No')?></a>
</div>
<?php } ?>