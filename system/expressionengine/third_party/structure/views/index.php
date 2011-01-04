<?php
$ul_open = false;
$last_page_depth = 0;
$i = 1;

?>

<div id="structure-ui">
	<?php if (empty($data)): ?>
		<div id="structure-instructions">
			<h1><?=lang('getting_started')?></h1>
			<?php
				$message = str_replace('%{link_to_settings}', BASE . "&C=addons_modules&M=show_module_cp&module=structure&method=channel_settings", lang('no_structure'));
				$message = str_replace('%{link_to_publish_tab}', BASE . "&C=publish", $message);
				echo $message;
			?>
		</div> <!-- close #structure-instructions -->
	<?php else: ?>

	<div class="unit-header"><h1 class="round"><?=lang('pages')?></h1></div>

	<ul id="page-ui" class="page-list">
	<?php foreach ($data as $eid => $page):?>

			<?php
				$edit_url = BASE . '&C=content_publish&M=entry_form&channel_id='.$page['channel_id'].'&entry_id='.$page['entry_id'];
				$add_url  = BASE . '&C=content_publish&M=entry_form&channel_id=' . $page['channel_id'] . '&parent_id=' . $eid . '&template_id=' . $site_pages['templates'][$eid];

				$li_open = '<li id="page-'. $page['entry_id'] . '" class="page-item">';

				// Start a sub nav
				if ($page['depth'] > $last_page_depth)
				{
					if ($permissions['reorder'] != 'y' && $permissions['admin'] != TRUE)
					{
						// Add indentation back if no permissions to reorder
						$markup = "<ul class=\"page-list\" style=\"margin-left:25px\">".$li_open."\n";
					}
					else
					{
						$markup = "<ul class=\"page-list\">".$li_open."\n";
					}
					$ul_open = true;
				}
				elseif ($i == 1)
				{
					$markup = $li_open."\n";
				}
				elseif ($page['depth'] < $last_page_depth)
				{
					$back_to = $last_page_depth - $page['depth'];
					$markup  = "\n</li>";
					$markup .= str_repeat("\n</ul>\n</li>\n", $back_to);
					$markup .= $li_open."\n";
					$ul_open = false;
				}
				else
				{
					$markup = "\n</li>\n".$li_open."\n";
				}

			?>
				<?=$markup;?>

				<div class="round item-wrapper">
					
					<p class="main-container">
					<span class="page-title">
						<img src="<?=$theme_url;?>/img/icon_handle.gif" alt="drag" class="sort-handle" />
						<a href="<?=$edit_url;?>" class="page-edit"><?=$page['title'];?></a>

						<!-- If Listing Exists -->
						<?php if ($page['listing_cid']): ?>
						<span class="addEdit">
							<span class="addEditLabel"><?=lang('entries')?>: </span>
							<a href="<?= BASE . "&C=content_publish&M=entry_form&channel_id=" . $page['listing_cid']; ?>" title="A<?=lang('add_an_entry_on_this_page')?>."><?=lang('add')?></a> 
							 / 
							<a href="<?= BASE . "&C=content_edit&channel_id={$page['listing_cid']}" ?>" title="<?=lang('edit_an_entry_on_this_page')?>."><?=lang('edit')?></a>
						</span>
						<?php endif; ?>
						
						<!-- If allowed to add page -->
						<?php if ($permissions['view_add_page']): ?>							
								<a class="action-add" href="#" title="Add child page" data-parent_id="<?=$eid?>">+<?=lang('ui_add_child_page')?></a>	
						<?php endif; ?>
							
					</span>
					</p> <!-- ///// close .main-container ///// -->
					
					<p class="extras-container">			
					<span class="extras">

						<!-- Show Page Type -->
						<?php if(isset($settings['show_page_type']) && $settings['show_page_type'] == 'y'):?>
							<span class="page_type_title">
								<?=$valid_channels[$page['channel_id']]['channel_title']?>
								<?php if($this->config->item('structure_show_page_id') == 'y'):?><small>(<?=$eid?>)</small><?php endif; ?>
							</span>
						<?php endif;?>

						<!-- Show Status -->
						<?php if(isset($settings['show_status']) && $settings['show_status'] == 'y'):?><span class="page_status"><?=ucfirst($page['status'])?></span><?php endif;?>
							
						<!-- Show View Page -->
						<?php if(isset($settings['show_view_page']) && $settings['show_view_page'] == 'y'):?><a class="action-view" href="<?=$this->functions->create_url($site_pages['uris'][$eid]);?>" target="_blank"><?=lang('view_page')?></a><?php endif;?>	
						
						<?php if (isset($permissions['delete']) && $permissions['delete']): ?><a class="action-delete" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=delete'.AMP.'toggle='.$page['entry_id'];?>"><?=lang('delete')?></a><?php endif;?>
						<input type="hidden" class="structurePid" value="<?=$page['parent_id']; ?>" />
						<input type="hidden" class="structureEid" value="<?=$eid; ?>" />
						
					</span>
					</p> <!-- ///// close .extras-container ///// -->
					
				</div> <!-- ///// close .item-wrapper ///// -->

		<?php 
		$last_page_depth = $page['depth']; $i++;
		endforeach;
		?>

		<?php
		// Close out the end
		$html  = "\n</li>";
		$html .= str_repeat("</ul>\n</li>\n", $last_page_depth);
		$ul_open = false;

		?>

		<?=$html?>

		</ul>

	
	<?php endif; ?>

	<?php if ($asset_data): ?>

	<div id="structure-assets">

		<div class="unit-header">
			<h1 class="round"><?=lang('assets')?></h1>
		</div><!-- close .unit-header -->

		<ul class="asset-list">
		<?php foreach ($asset_data as $channel_id => $field): ?>
			<li>
				<?=$field['channel_title'];?>
				<span class="listing-controls">
					<a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id?>" class="listing-add"><?=lang('add')?></a>
					/
					<a href="<?=BASE.AMP.'C=content_edit'.AMP.'channel_id='.$channel_id?>" class="listing-edit"><?=lang('edit')?></a>
				</span>
			</li>
		<?php endforeach; ?>
		</ul>

	</div> <!-- close #structure-assets -->

	<?php endif; ?>
	
	
	<!-- If show_picker is "yes" and multiple channels exist -->
	<?php if (isset($settings['show_picker']) && $settings['show_picker'] == 'y'):?>

		<div class="overlay">
			<h2><?=lang('choose_page_type')?></h2>
			<ul class="listing" id="overlay_listing">
			<?php foreach ($valid_channels as $key => $channel): ?>
				<li><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$key.AMP.'template_id='.$channel['template_id']?>"><?=$channel["channel_title"];?></a></li>
			<?php endforeach; ?>
			</ul>
			<span class="close"><a href="javascript:;"><?=lang('cancel')?></a></span>
	<?php endif; ?>
	<div class="clear"></div>
</div>
</div>