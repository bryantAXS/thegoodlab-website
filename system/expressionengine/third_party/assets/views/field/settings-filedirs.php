<div class="assets-filedirs">
	<div class="assets-all">
		<label><?=form_checkbox('assets[filedirs]', 'all', ($data == 'all'), 'onchange="Assets.onAllFiledirsChange(this)"')?>&nbsp;&nbsp;<?=lang('all')?></label>
	</div>
	<div class="assets-list">
		<?php if (! $filedirs->num_rows()): ?>
			<?=lang('no_file_upload_directories')?>
		<?php else: ?>
			<?php foreach ($filedirs->result() as $filedir): ?>
				<label><?=form_checkbox('assets[filedirs][]', $filedir->id, ($data == 'all' || in_array($filedir->id, $data)), ($data == 'all' ? 'disabled="disabled"' : ''))?>&nbsp;&nbsp;<?=$filedir->name?></label><br/>
			<?php endforeach ?>
		<?php endif ?>
	</div>
</div>
