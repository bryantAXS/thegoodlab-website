<? if($settings['any_channels'] && $settings['any_templates'] > 0) { ?>

	<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=ep_better_workflow');?>
	<?=form_hidden('status_group_id', $settings['status_group_id'])?>
	<?=form_hidden('open_status_id', $settings['open_status_id'])?>
	<?=form_hidden('bwf_ajax_url', $settings['ajax_url'])?>

	<h3>Channel settings</h3>
	<?php 


		$this->table->set_template($cp_pad_table_template);
		
		$this->table->set_heading(	
			lang('bwf_channel'),
			lang('bwf_is_used'),
			lang('bwf_preview_template'),
			lang('bwf_email_notification')
		);

		foreach ($settings['channels'] as $c)
		{
			$this->table->add_row( 
				array("data" => $c['title'], "width" => "20%"),
				array("data" => $c['uses_workflow'], "width" => "40%"),
				array("data" => $c['template'], "width" => "20%"), 
				array("data" => $c['notification_group'], "width" => "20%")
			);
		}

		echo $this->table->generate();
		$this->table->clear();
	?>

	<h3>Group settings</h3>
	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
			array('data' => lang('User group'), 'style' => 'width:50%;'),
			lang('Workflow role')
		);

		foreach ($settings['groups'] as $g) {
			$this->table->add_row( $g['name'], $g['bwf_role']);
		}
	
		echo $this->table->generate();

	?>

	<h3>Advanced</h3>
	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
		    lang('Property'), 
		    lang('Setting')
		);

		foreach ($settings['advanced'] as $g) {
			$this->table->add_row( $g['name'], $g['radio']);
		}

		echo $this->table->generate();
	?>
	
	<?=form_submit('submit', lang('submit'), 'class="submit"')?>
	<?=form_close()?>

<? } else { ?>

	<h3>Better Workflow settings</h3>
	
	<p>Sorry, you're not quite ready to configure Better Workflow yet.</p>
		
	<? if($settings['any_templates'] == 0) echo "<p>There are curently no templates available. <a href=\"".BASE.AMP."C=design".AMP."M=manager\">Create some here</a>.</p>"; ?>
	
	<? if(!$settings['any_channels']) echo "<p>There are curently no channels available. <a href=\"".BASE.AMP."C=admin_content".AMP."M=channel_management\">Create some here</a>.</p>"; ?>
<? } ?>
