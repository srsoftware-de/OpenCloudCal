<?php
include 'forms.php';
$start_sec=strtotime($appointment->start);
$end=date($db_time_format,$start_sec+3600);
?>
<div class="addsession">
	<h2>
		<?php echo loc('add session');?>
	</h2>
	<form class="addsession" method="POST">
		<?php echo '<input type="hidden" name="newsession[aid]" value="'.$appointment->id.'" />'.PHP_EOL;?>
		<div class="start">
			<?php echo loc('start'); datepicker('newsession[start]',$appointment->start); echo loc('start time'); timepicker('newsession[start]',$appointment->start); ?>
		</div>
		<div class="end">
			<?php echo loc('end (optional)'); datepicker('newsession[end]',$end); echo loc('end time'); timepicker('newsession[end]',$end) ?>
		</div>
		<div id="description">
			<?php echo loc('description'); ?>
			<input type="text" name="newsession[description]" />
		</div>
	  <?php echo '<input type="submit" value="'.loc('add session').'"/><br/>'.PHP_EOL; ?>
	</form>
</div>
