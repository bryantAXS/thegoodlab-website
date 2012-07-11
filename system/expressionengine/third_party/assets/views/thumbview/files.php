<?php
	if (! isset($files))          $files = array();
	if (! isset($field_name))     $field_name = FALSE;
	if (! isset($disabled_files)) $disabled_files = array();

	foreach ($files as $i => &$file):

		// -------------------------------------------
		//  Thumbnail
		// -------------------------------------------

		$is_image = ($helper->get_kind($file->server_path()) == 'image');
		$has_thumb = FALSE;

		// is this an image?
		if ($is_image)
		{
			// figure out where the thumb should be
			$folder_subpath = dirname($file->subpath()).'/';
			$thumb_subpath = (version_compare(APP_VER, '2.1.5', '<') ? '_thumbs/thumb_' : (APP_VER == '2.1.5' ? '_thumb/' : '_thumbs/')) . $file->filename();
			$thumb_path = $file->filedir_path() . $folder_subpath . $thumb_subpath;

			// do we need to create a thumbnail?
			if (! file_exists($thumb_path))
			{
				if (version_compare(APP_VER, '2.1.5', '>='))
				{
					$this->filemanager->create_thumb(
						$file->server_path(),
						array(
							'server_path' => $file->filedir_path().$folder_subpath, 
							'file_name'   => $file->filename(),
							'dimensions'  => array()
						)
					);
				}
				else
				{
					$this->filemanager->create_thumb(
						array('server_path' => $file->filedir_path().$folder_subpath), 
						array('name' => $file->filename())
					);
				}
			}

			// does it exist now?
			if (file_exists($thumb_path))
			{
				$has_thumb = TRUE;
				$thumb_url = $file->filedir_url() . $folder_subpath . $thumb_subpath;

				// get the thumb size
				$thumb_size = getimagesize($thumb_path);
				$thumb_width = $thumb_size[0];
				$thumb_height = $thumb_size[1];

				// set the thumb padding so that div.assets-tv-thumb's height is 64px
				$thumb_top_padding = floor((64 - $thumb_height) / 2);
				$thumb_bottom_padding = 64 - $thumb_height - $thumb_top_padding;
			}
		}

		// use the default thumb image if there's no thumb
		if (! $has_thumb)
		{
			$thumb_url = PATH_CP_GBL_IMG.'default.png';
			$thumb_width = 64;
			$thumb_height = 64;
		}

		// split the filename up so the filename wraps where we want it to
		$chunks = preg_split('/([\-_\. ]+)/', $file->filename(), -1, PREG_SPLIT_DELIM_CAPTURE);
?>
	<li data-path="<?=$file->path()?>" data-file-url="<?=$file->url()?>" class="assets-tv-file <?php if ($has_thumb): ?>assets-tv-hasthumb<?php endif ?><?php if (in_array($file->path(), $disabled_files)): ?> assets-disabled<?php endif ?><?php if ($file->selected): ?> assets-selected<?php endif ?>">
		<div class="asests-tv-thumb" <?php if ($has_thumb): ?>style="padding: <?=$thumb_top_padding?>px 0 <?=$thumb_bottom_padding?>px"<?php endif ?>><img src="<?=$thumb_url?>" width="<?=$thumb_width?>" height="<?=$thumb_height?>" alt="<?=$file->filename()?>" /></div>
		<div class="assets-tv-filename"><?php for ($i = -1; $i < count($chunks)-1; $i += 2): ?><span><?=($i!=-1?$chunks[$i]:'').$chunks[$i+1]?></span><?php endfor ?></div>
		<?php if ($field_name): ?><input type="hidden" name="<?=$field_name?>[]" value="<?=$file->path()?>" /><?php endif ?>
	</li>
<?php endforeach ?>
