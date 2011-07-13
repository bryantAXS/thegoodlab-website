<?php
	foreach ($folders as $folder):

		if (! file_exists($folder[1]) || ! is_dir($folder[1])) continue;

		$id = (isset($folder[2]) && $folder[2]) ? $folder[2] : $id_prefix.$folder[0].'/';

		$padding = 20 + (18 * $depth);
?>
	<li class="assets-fm-folder">
		<a data-id="<?=$id?>" style="padding-left: <?=$padding?>px"><span class="assets-fm-icon"></span><?=$folder[0]?>  </a>
	</li>
<?php
	endforeach
?>
