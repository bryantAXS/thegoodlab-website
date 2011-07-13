<?php
	if (! isset($show_cols))      $show_cols = array('date', 'size');
	if (! isset($files))          $files = array();
	if (! isset($start_index))    $start_index = 0;
	if (! isset($field_name))     $field_name = FALSE;
	if (! isset($disabled_files)) $disabled_files = array();

	foreach ($files as $i => &$file):
		$index = $start_index + $i;
		$helper->prep_file_for_view($file);
?>
	<tr data-file-path="<?=$file['file_path']?>" data-file-url="<?=$file['url']?>" class="assets-<?=($index % 2 ? 'even' : 'odd')?> <?php if (in_array($file['file_path'], $disabled_files)): ?>assets-disabled<?php endif ?>">
		<td class="assets-lv-name"><div class="assets-kind assets-<?=$helper->get_kind($file['full_path'])?>"></div><?=$file['file_name']?><?php if ($field_name): ?><input type="hidden" name="<?=$field_name?>[]" value="<?=$file['file_path']?>" /><?php endif ?></td>
		<?php if (in_array('folder', $show_cols)): ?><td class="assets-lv-folder"><?=str_replace('/', '<span>/</span>', $file['folder'])?></td><?php endif ?>
		<?php if (in_array('date',   $show_cols)): ?><td><?=$helper->format_date($file['date'])?></td><?php endif ?>
		<?php if (in_array('size',   $show_cols)): ?><td><?=$helper->format_filesize($file['file_size'])?></td><?php endif ?>
	</tr>
<?php
	endforeach
?>
