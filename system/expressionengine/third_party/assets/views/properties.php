<div class="assets-filename"><div class=" assets-<?=$kind?>"><?=$file_name?></div></div>

<div class="assets-filedata">
	<table cellspacing="0" cellpadding="0" border="0">
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('size')?></th>
			<td><?=$file_size?></td>
		</tr>
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('kind')?></th>
			<td><?=ucfirst(lang($kind))?></td>
		</tr>
	<?php if ($kind == 'image'): ?>
		<tr class="assets-fileinfo">
			<th scope="row"><?=lang('image_size')?></th>
			<td><?=$image_size?></td>
		</tr>
	<?php endif ?>

		<tr class="assets-spacer"><th></th><td></td></tr>

		<tr class="assets-title assets-odd">
			<th scope="row"><?=lang('title')?></th>
			<td><textarea rows="1"><?=$title?></textarea></td>
		</tr>
		<tr class="assets-date">
			<th scope="row"><?=lang('date')?></th>
			<td><input type="text" value="<?=$date?>" /></td>
		</tr>
		<tr class="assets-alt_text assets-odd">
			<th scope="row"><?=lang('alt_text')?></th>
			<td><textarea rows="1"><?=$alt_text?></textarea></td>
		</tr>
		<tr class="assets-caption">
			<th scope="row"><?=lang('caption')?></th>
			<td><textarea rows="1"><?=$caption?></textarea></td>
		</tr>
		<tr class="assets-desc assets-odd">
			<th scope="row"><?=lang('description')?></th>
			<td><textarea rows="1"><?=$desc?></textarea></td>
		</tr>
		<tr class="assets-author">
			<th scope="row"><?=lang($author_lang)?></th>
			<td><textarea rows="1"><?=$author?></textarea></td>
		</tr>
		<tr class="assets-location assets-odd">
			<th scope="row"><?=lang('location')?></th>
			<td><textarea rows="1"><?=$location?></textarea></td>
		</tr>
		<tr class="assets-keywords">
			<th scope="row"><?=lang('keywords')?></th>
			<td><textarea rows="1"><?=$keywords?></textarea></td>
		</tr>
	</table>
</div>
