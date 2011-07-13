<?php echo $this->view('mcp_header'); ?>

<?=form_open($base_url_short.AMP.'method=do_import')?>
<table class="mainTable">
	<thead>
		<tr>
			<th colspan="2"><?=lang('tagger:import:solspace')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?=lang('tagger:import:channel')?></strong></td>
			<td>
				<?php foreach($channels AS $channel_id => $channel_title):?>
				<input name="channels[]" type="checkbox" value="<?=$channel_id?>" />&nbsp;&nbsp;<?=$channel_title?>&nbsp;&nbsp;&nbsp;
				<?php endforeach;?>
			</td>
		</tr>
	</tbody>
</table>

<input name="submit" class="submit" type="submit" value="<?=lang('tagger:import')?>"/>

<?=form_close()?>