<?php
	// default values
	if (! isset($show_cols))  $show_cols = array('date', 'size');
	if (! isset($orderby))    $orderby = FALSE;
	if (! isset($sort))       $sort = 'asc';
	if (! isset($files))      $files = array();
	if (! isset($limit))      $limit = FALSE;
	if (! isset($field_name)) $field_name = FALSE;
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
<?php

	if ($files)
	{
		// -------------------------------------------
		//  Make sure we know the full path, file name, file size, and date for each of the files
		// -------------------------------------------

		foreach ($files as &$file)
		{
			$helper->prep_file_for_view($file);

			// prep sort arrays?
			if ($orderby)
			{
				$sort_names[] = strtolower($file['file_name']);
				$sort_folders[] = $file['folder'];
				if ($orderby == 'file_size') $sort_sizes[] = $file['file_size'];
				else if ($orderby == 'date') $sort_dates[] = $file['date'];
			}
		}

		// -------------------------------------------
		//  Sorting
		// -------------------------------------------

		if ($orderby)
		{
			$SORT = $sort == 'asc' ? SORT_ASC : SORT_DESC;

			switch ($orderby)
			{
				case 'name':
					// sort by name, then folder
					array_multisort($sort_names, $SORT, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
					break;

				case 'folder':
					// sort by folder, then name
					array_multisort($sort_folders, $SORT, SORT_STRING, $sort_names, SORT_ASC, SORT_STRING, $files);
					break;

				case 'date':
					// sort by date, then name, then folder
					array_multisort($sort_dates, $SORT, SORT_NUMERIC, $sort_names, SORT_ASC, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
					break;

				case 'file_size':
					// sort by size, then name, then folder
					array_multisort($sort_sizes, $SORT, SORT_NUMERIC, $sort_names, SORT_ASC, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
					break;
			}
		}

		// -------------------------------------------
		//  Enforce the limit
		// -------------------------------------------

		if ($limit && count($files) > $limit)
		{
			$files = array_slice($files, 0, $limit);
		}

		// -------------------------------------------
		//  Show the files
		// -------------------------------------------

		// pass in the new $files array
		$vars['files'] = $files;

		echo $this->load->view('listview/files', $vars);
	}
?>
			</tbody>
		</table>
	</div>
</div>
