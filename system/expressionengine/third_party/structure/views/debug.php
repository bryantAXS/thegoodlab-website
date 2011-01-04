<?php if ($duplicate_entries > 0):?>
<h2>You currently have <span style="color:#E7174B"><?=$duplicate_entries?></span> extraneous entries in the exp_structure table.</h2>
<br />
<?=form_open($action_url, $attributes)?>

	<?=form_submit(array('name' => 'submit', 'value' => 'Fix It!', 'class' => 'submit'))?>

<?=form_close()?>
<br />
<?php endif ?>

<?php if ($duplicate_lefts > 0 || $duplicate_rights > 0): ?>
<h2>You <?php if ($duplicate_entries > 0):?>also <?php endif ?>have Duplicate Left and/or Right values.</h2>
<p>Head over to <a href="http://structure.tenderapp.com">Structure Support</a> and we'll help sort you out.</p>
<?php endif ?>

<?php if ($duplicate_lefts == 0 && $duplicate_rights == 0 && $duplicate_entries == 0): ?>
	<p>You're probably in better shape than you think. Head over to <a href="http://structure.tenderapp.com" target="_blank">Structure Support</a> and we'll help sort you out anything else that might be going on.</p>
<?php endif?>