<?php
if ($format=='html') {
	include 'forms.php';
	$start=$appointment->start;
	$start_sec=strtotime($start);
	if (isset($appointment->sessions)){
		foreach ($appointment->sessions as $session){
			$session_end=strtotime($session->end);
			if ($session_end>$start_sec){
				$start_sec=$session_end;
				$start=$session->end;
			}
		}
	}
	$end=date(TIME_FMT,$start_sec+3600);
	?>
<div class="addsession">
	<h2>
		<?php echo loc('add session');?>
	</h2>
	<form class="addsession" method="POST"
		action="?show=<?php echo $appointment->id; ?>">
		<?php echo '<input type="hidden" name="newsession[aid]" value="'.$appointment->id.'" />'.PHP_EOL;?>
		<div class="start">
			<?php echo loc('start'); datepicker('newsession[start]',$start); echo loc('start time'); timepicker('newsession[start]',$start); ?>
		</div>
		<div class="end">
			<?php echo loc('end (optional)'); datepicker('newsession[end]',$end); echo loc('end time'); timepicker('newsession[end]',$end) ?>
		</div>
		<div id="description">
			<?php echo loc('description'); ?>
			<input type="text" name="newsession[description]" />
		</div>
		<div class="submit">
			<div class="choice">
		  	<input type="radio" id="addsession" name="nextaction" value="addsession" />
		  	<label for="addsession">
					<?php echo loc('Add a session to this appointment in the next step.'); ?>
				</label>
			</div>
			<div class="choice">
				<input type="radio" id="addlink" name="nextaction" value="addlink" />
				<label for="addlink">
					<?php echo loc('Add a link to this appointment in the next step.'); ?>
				</label>
			</div>
			<div class="choice">
				<input type="radio" id="addattachment" name="nextaction" value="addattachment" />
				<label for="addattachment">
					<?php echo loc('Add an attachment to this appointment in the next step.'); ?>
				</label>
			</div>
			<div class="choice">
				<input type="checkbox" id="gricalpost" name="gricalpost" <?php echo gricalValue(); ?>/>
				<label for="gricalpost">
					<?php echo loc('Send this appointment to grical, too.').'*'; ?>
				</label>
			</div>
			<div class="choice">
				<input type="checkbox" id="calciferpost" name="calciferpost"  <?php echo calciferValue(); ?>/>
				<label for="calciferpost">
					<?php echo loc('Send this appointment to calcifer, too.').'*'; ?>
				</label>
			</div>
			<?php echo '<input type="submit" value="'.loc('add session').'"/><br/>'.PHP_EOL; ?>
		</div>
	</form>
</div>
<?php } ?>