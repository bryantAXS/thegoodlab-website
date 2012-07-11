<div id="<?=$field_id?>" class="assets-field">
	<?=$this->load->view($file_view)?>
</div>
<div class="assets-field-btns <?php if (! $multi): ?>assets-single<?php endif ?>">
	<a class="assets-btn assets-btn-big assets-add <?php if (! $multi && $files): ?>assets-disabled<?php endif ?>"><span></span><?=lang($multi ? 'add_files' : 'add_file')?></a>
	<a class="assets-btn assets-btn-big assets-remove <?php if ($multi || ! $files): ?>assets-disabled<?php endif ?>"><span></span><?=lang($multi ? 'remove_files' : 'remove_file')?></a>
</div>
