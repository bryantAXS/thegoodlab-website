<div class="assets-filename"><div class=" assets-<?=$file->kind()?>"><?=$file->filename()?></div></div>

<div class="assets-filedata">
	<table cellspacing="0" cellpadding="0" border="0">
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('size')?></th>
			<td><?=$helper->format_filesize($file->size())?></td>
		</tr>
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('kind')?></th>
			<td><?=ucfirst(lang($file->kind()))?></td>
		</tr>
	<?php if ($file->kind() == 'image'): ?>
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('image_size')?></th>
			<td><?=$file->width().' &times; '.$file->height()?></td>
		</tr>
	<?php endif ?>

		<tr class="assets-spacer"><th></th><td></td></tr>

		<tr>
			<th scope="row"><?=lang('title')?></th>
			<td><textarea name="title" rows="1" data-maxl="100"><?=$file->row('title')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('date')?></th>
			<td><input name="date" type="text" data-type="date" <?php if ($file->row('date')): ?>data-default-date="<?=$this->localize->set_localized_time($file->row('date')) * 1000?>" value="<?=$this->localize->set_human_time($file->row('date'))?>"<?php endif ?> /></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('alt_text')?></th>
			<td><textarea name="alt_text" rows="1" data-maxl="255"><?=$file->row('alt_text')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('caption')?></th>
			<td><textarea name="caption" rows="1" data-maxl="255"><?=$file->row('caption')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('description')?></th>
			<td><textarea name="desc" rows="1" data-maxl="65535" data-multiline="1"><?=$file->row('desc')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang($author_lang)?></th>
			<td><textarea name="author" rows="1" data-maxl="255"><?=$file->row('author')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('location')?></th>
			<td><textarea name="location" rows="1" data-maxl="255"><?=$file->row('location')?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><?=lang('keywords')?></th>
			<td><textarea name="keywords" rows="1" data-maxl="65535" data-multiline="1"><?=$file->row('keywords')?></textarea></td>
		</tr>
<?php
	// -------------------------------------------
	//  'assets_meta_add_row' hook
	//   - Allows extensions to add extra metadata rows to the file properties HUD
	// 
		if ($this->extensions->active_hook('assets_file_meta_add_row'))
		{
			echo $this->extensions->call('assets_file_meta_add_row', $file);
		}
	// 
	// -------------------------------------------
?>
	</table>
</div>
