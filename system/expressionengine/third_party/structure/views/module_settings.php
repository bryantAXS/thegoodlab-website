<?php if ( ! $permissions['admin']) $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');?>
<?=form_open($action_url, $attributes)?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr class="odd">
			<th><?=lang('dashboard_preferences')?></th>
			<th style="width:250px"><?=lang('setting')?></th>
		</tr>
	</thead>
	<tbody>	
		<tr class="odd">
			<td><?=lang('settings_enable_picker'); ?></td>
			<td>
				<select name="show_picker">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['show_picker'] == '' || $settings['show_picker'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['show_picker'] == '' || $settings['show_picker'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
		<tr class="even">
			<td><?=lang('show_view_page_links')?></td>
			<td>
				<select name="show_view_page">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['show_view_page'] == '' || $settings['show_view_page'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['show_view_page'] == '' || $settings['show_view_page'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
		<tr class="odd">
			<td><?=lang('show_page_statuses')?></td>
			<td>
				<select name="show_status">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['show_status'] == '' || $settings['show_status'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['show_status'] == '' || $settings['show_status'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
		<tr class="even">
			<td><?=lang('show_page_type')?></td>
			<td>
				<select name="show_page_type">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['show_page_type'] == '' || $settings['show_page_type'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['show_page_type'] == '' || $settings['show_page_type'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
		<tr class="odd">
			<td><?=lang('show_global_add_page_button')?></td>
			<td>
				<select name="show_global_add_page">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['show_global_add_page'] == '' || $settings['show_global_add_page'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['show_global_add_page'] == '' || $settings['show_global_add_page'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
	</tbody>
</table>
<?php if($extension_is_installed === TRUE):?>
<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr class="odd">
			<th><?=lang('cp_preferences')?></th>
			<th style="width:250px"><?=lang('setting')?></th>
		</tr>
	</thead>
	<tbody>	
		<tr class="odd">
			<td><?=lang('setting_redirect_login')?></td>
			<td>
				<select name="redirect_on_login">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['redirect_on_login'] == '' || $settings['redirect_on_login'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['redirect_on_login'] == '' || $settings['redirect_on_login'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
		<tr class="even">
			<td><?=lang('setting_redirect_publish')?></td>
			<td>
				<select name="redirect_on_publish">
					<option value="y"<?=set_select('yes', 'Yes',  ($settings['redirect_on_publish'] == '' || $settings['redirect_on_publish'] == 'y' ? 'y' : ''));?>><?=lang('yes')?></option>
					<option value="n"<?=set_select('no', 'No', ($settings['redirect_on_publish'] == '' || $settings['redirect_on_publish'] == 'n' ? 'y' : ''));?>><?=lang('no')?></option>
				</select>
			</td>
		</tr>
	</tbody>
</table>
<?php endif;?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr class="even">
			<th><?=lang('member_group_permission')?></th>
			<?php if ($groups) : ?>
				<?php foreach ($groups as $group): ?>
					<th><?=$group['title'];?></th>
				<?php endforeach; ?>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
		<?php if ( ! $groups): ?>
			<tr class="box">
				<td>
					<p><strong><?=lang('no_groups')?></strong></p>
					<ul>				
						<li><?=lang('access_control_panel')?></li>
						<li><?=lang('access_edit')?></li>
						<li><?=lang('access_structure')?></li>
					</ul>
				</td>
			</tr>
		<?php else: ?>
			<?php $i = 0; foreach ($perms as $perm_id => $perm): ?>
				<tr class="<?php echo ($i++ % 2) ? 'even' : 'odd'; ?>">
					<td><?=$perm?></td>
					<?php foreach ($groups as $group): $perm_key = $perm_id . '_' . $group['id']; ?>
					<td class="settingsPermBoxes">
						<input type="checkbox" name="<?=$perm_key; ?>" id="<?=$perm_key; ?>" class="<?=$perm_id . ' group' . $group['id']; ?>" value="<?=$group['id']; ?>"<?php if (isset($settings[$perm_key])) echo ' checked="checked"'; ?> />
					</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>


<?=form_submit(array('name' => 'submit', 'value' => 'Save', 'class' => 'submit'))?>
<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure';?>" style="margin-left:10px;"><?=lang('cancel')?></a>
<?=form_close()?>