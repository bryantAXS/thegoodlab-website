<?php
    $this->EE =& get_instance();
	$this->EE->cp->load_package_css('settings');

	if(isset($debug)) : ?>
	<pre><code><?php print_r($current); ?></code></pre>
	<?php endif; ?>
	
	<?php
	if(!isset($current['token']) || $current['token'] == '' || isset($connection_error)) : ?>
		
		<?php if(isset($connection_error)) : ?>
		<p class="ga_error"><?= $connection_error ?></p>
		<?php endif; ?>
		
		<p class="ga_intro"><?= $this->EE->lang->line('analytics_instructions_1') ?> <a href="<?= $authsub_url ?>"><?= $this->EE->lang->line('analytics_instructions_2') ?></a></p>
		
		<p><?= $this->EE->lang->line('analytics_instructions_3') ?> <a href="http://code.google.com/apis/accounts/docs/RegistrationForWebAppsAuto.html#new"><?= $this->EE->lang->line('analytics_instructions_4') ?></a> <?= $this->EE->lang->line('analytics_instructions_5') ?></p>
		
	<?php else :
	
		echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings', array('id' => $file), array('file' => $file));
		
		$this->table->set_template($cp_pad_table_template);
		
		$this->table->set_heading(
			array('data'=> NBS), array('data'=> NBS)
		);
		
		$this->table->add_row(
			$this->EE->lang->line('analytics_authentication'),
			$this->EE->lang->line('analytics_authenticated').
			' &nbsp; (<a href="'.
				BASE.AMP.'C=addons_extensions'.
				AMP.'M=extension_settings'.
				AMP.'file='.$file.
				AMP.'reset=y'.
				'">'.$this->EE->lang->line('analytics_reset').'</a>)'
		);		
	
		if(isset($ga_profiles))
		{
			$this->table->add_row(
				form_label($this->EE->lang->line('analytics_profile'), 'profile'),
				form_dropdown('profile', $ga_profiles, (isset($current['profile'])) ? $current['profile'] : '', 'id="profile"')
			);		
		}
		else
		{
			$this->table->add_row(
				$this->EE->lang->line('analytics_profile'),
				'<span class="failure">'.$this->EE->lang->line('analytics_no_accounts').'</span>'
			);			
		}
		
		foreach($radio_settings as $setting)
		{
			$this->table->add_row(
				form_label($this->EE->lang->line($setting)),
				form_label(form_radio($setting, 'y', (isset($current[$setting]) && $current[$setting] == 'y') ? TRUE : FALSE).NBS.$this->EE->lang->line('yes')).NBS.NBS.form_label(form_radio($setting, 'n', (isset($current[$setting]) && $current[$setting] == 'n') ? TRUE : FALSE).NBS.$this->EE->lang->line('no'))
			);
		}

		foreach($text_settings as $setting)
		{
			$extra = 'id="'.$setting.'"';
			if($v = $this->EE->config->item('cp_analytics_'.$setting))
			{
				$extra = ' disabled="disabled"';
			}
			elseif(isset($current[$setting]) && !empty($current[$setting]))
			{
				$v = $current[$setting];
			}
			else
			{
				$v = ($setting == 'cache_path') ?
					rtrim(PATH_THEMES, '/').'/third_party/cp_analytics/' : 
					rtrim($this->EE->config->item('theme_folder_url'), '/').'/third_party/cp_analytics/';
			}
			$this->table->add_row(
				form_label($this->EE->lang->line($setting), $setting),
				form_input($setting, $v, $extra)
			);
		}
																			
		echo $this->table->generate();
		
		echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('analytics_save_settings'), 'class' => 'submit'));
		echo form_close();
		
	endif;
?>