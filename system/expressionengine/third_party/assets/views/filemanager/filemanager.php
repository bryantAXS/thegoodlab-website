<?php
	if (! isset($filedirs)) $filedirs = 'all';
	if (! isset($site_id))  $site_id = NULL;
?>
<div class="assets-fm">
	<div class="assets-fm-toolbar">
		<ul class="assets-fm-view">
			<li><a class="assets-btn assets-btn-big assets-fm-thumbs assets-active" title="<?=lang('view_files_as_thumbnails')?>"><span></span></a></li>
			<li><a class="assets-btn assets-btn-big assets-fm-list" title="<?=lang('view_files_in_list')?>"><span></span></a></li>
		</ul>

		<div class="assets-fm-upload"></div>

		<div class="assets-fm-filter assets-fm-search">
			<label><span title="<?=lang('keyword_search')?>"></span><input type="text" /></label>
			<a class="assets-fm-erase" title="<?=lang('erase_keywords')?>"></a>
		</div>
		<div class="assets-fm-spinner"></div>
	</div>

	<div class="assets-fm-left">
		<div class="assets-fm-folders assets-scrollpane">
			<ul>
<?php
	$vars['helper'] = $helper;
	$vars['depth'] = 1;

	$vars['folders'] = array();

	foreach ($helper->get_filedir_prefs($filedirs, $site_id) as $filedir)
	{
		if ($helper->is_folder($filedir->server_path))
		{
			$vars['folders'][] = array(
				'name' => $filedir->name,
				'path' => "{filedir_{$filedir->id}}"
			);
		}
	}

	$this->load->view('filemanager/folders', $vars);
?>
			</ul>
		</div>
		<div class="assets-footer">&nbsp;</div>
	</div>

	<div class="assets-fm-right">
		<div class="assets-fm-files">
			<?php $this->load->view('thumbview/thumbview'); ?>
		</div>

		<div class="assets-footer">
			<div class="assets-fm-status">0 <?=lang('files')?></div>
			<div class="assets-fm-btns">
<?php if ($mode == 'full'): ?>
				<a class="assets-btn assets-disabled"><?=lang('edit_file')?></a>
<?php else: ?>
				<a class="assets-btn"><?=lang('cancel')?></a>
				<a class="assets-btn assets-submit assets-disabled"><?=lang($multi ? 'add_files' : 'add_file')?></a>
<?php endif ?>
			</div>
		</div>
	</div>

	<div class="assets-fm-uploadprogress">
		<div class="assets-fm-progressbar">
			<div class="assets-fm-pb-bar"></div>
		</div>
	</div>
</div>
