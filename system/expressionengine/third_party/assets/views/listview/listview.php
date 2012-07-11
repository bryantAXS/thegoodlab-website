<?php
	// default values
	if (! isset($show_cols))  $show_cols = array('date', 'size');
	if (! isset($orderby))    $orderby = FALSE;
	if (! isset($sort))       $sort = 'asc';
?>

<div class="assets-listview">
	<div class="assets-lv-thead">
		<table border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th data-orderby="name" class="assets-lv-name <?php if ($orderby == 'name'): ?>assets-lv-sorting assets-lv-<?=$sort?><?php endif ?>"><?=lang('name')?></th>
					<?php if (in_array('folder', $show_cols)): ?><th data-orderby="folder" class="assets-lv-folder <?php if ($orderby == 'folder'): ?>assets-lv-sorting assets-lv-<?=$sort?><?php endif ?>"><?=lang('folder')?></th><?php endif ?>
					<?php if (in_array('date',   $show_cols)): ?><th data-orderby="date" <?php if ($orderby == 'date'): ?>class="assets-lv-sorting assets-lv-<?=$sort?>"<?php endif ?>><?=lang('date_modified')?></th><?php endif ?>
					<?php if (in_array('size',   $show_cols)): ?><th data-orderby="file_size" <?php if ($orderby == 'file_size'): ?>class="assets-lv-sorting assets-lv-<?=$sort?>"<?php endif ?>><?=lang('size')?></th><?php endif ?>
				</tr>
			</thead>
		</table>
	</div>

	<div class="assets-lv-tbody assets-scrollpane">
		<table border="0" cellspacing="0" cellpadding="0">
			<tbody>
				<?=$this->load->view('listview/files')?>
			</tbody>
		</table>
	</div>
</div>
