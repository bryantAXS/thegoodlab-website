<?php

echo form_open($base.AMP.'method=save_settings');

$this->table->set_template($cp_table_template);
$this->table->set_heading(array(array('style' => 'width: 50%', 'data' => lang('preference')), lang('setting')));


$this->table->add_row(
	lang('license_key', 'license_key'),
	form_input('license_key', $license_key, 'id="license_key" style="width: 98%"')
);


echo $this->table->generate();

echo form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'));
echo form_close();

?>
