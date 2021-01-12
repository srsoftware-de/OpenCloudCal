<?php if ($format=='html') {
include 'forms.php';
?>
<form class="adddate" method="POST">
	<h2>Neuen Termin anlegen</h2>
	<div class="left">
		<div id="title">
			<?php echo loc('title'); ?>
			<input type="text" name="newappointment[title]" />
		</div>
		<div id="email">
			<?php echo loc('email - bots only'); ?>
			<input type="text" name="newappointment[email]" />
		</div>
		<div class="start">
			<?php echo loc('start date'); datepicker('newappointment[start]'); echo loc('start time'); timepicker('newappointment[start]',date(TIME_FMT)); ?>
		</div>
		<div class="end">
			<?php echo loc('end date (optional)'); datepicker('newappointment[end]'); echo loc('end time'); timepicker('newappointment[end]') ?>
		</div>
		<div id="location">
			<?php echo loc('location'); ?>
			<input type="text" name="newappointment[location]" />
		</div>
		<div id="coordinates">
			<?php echo loc('coordinates'); ?>
			<input type="text" id="coords" name="newappointment[coordinates]" />
		</div>
		<div id="description">
			<?php echo loc('description'); ?>
			<textarea name="newappointment[description]"></textarea>
		</div>

		<div id="tags">
			<?php echo loc('tags'); ?>
			<?php echo '<input type="text" name="newappointment[tags]" ';
			if (count($selected_tags)>0){
        echo 'value="';
        $val='';
        foreach ($selected_tags as $tag){
          $val.=$tag.' ';
        }
        echo trim($val).'" ';
			}
			echo '/>'; ?>
		</div>
	</div>
	<div class="right">
		<?php include 'openlayers.php'; ?>
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
				<input type="checkbox" id="gricalpost" name="gricalpost" />
				<label for="gricalpost">
					<?php echo loc('Send this appointment to grical, too.').'*'; ?>
				</label>
			</div>
			<div class="choice">
				<input type="checkbox" id="calciferpost" name="calciferpost" />
				<label for="calciferpost">
					<?php echo loc('Send this appointment to calcifer, too.').'*'; ?>
				</label>
			</div>

		</div>
	<div class="submit">
  		<?php echo '<span class="note">'.loc('* You should complete your appointment with all sessions and links, before submitting to calcifer or grical!').'</span><br/>'.PHP_EOL; ?>
		<?php echo '<input type="submit" value="'.loc('create new appointment').'"/>&nbsp;'; ?>

	</div>
</form>
<?php } ?>