<?php
	if (isset($folders) && $folders):

		// -------------------------------------------
		//  Sort folders alphabetically
		// -------------------------------------------

		foreach ($folders as $folder)
		{
			$sort_names[] = strtolower($folder['name']);
		}

		array_multisort($sort_names, SORT_ASC, SORT_STRING, $folders);


		foreach ($folders as $folder):
?>
	<li class="assets-fm-folder">
		<a data-path="<?=$folder['path']?>" style="padding-left: <?=(20 + (18 * $depth))?>px"><span class="assets-fm-icon"></span><?=$folder['name']?>  </a>
	</li>
<?php
		endforeach;
	endif;
?>
