<?php if (empty($sets)): ?>

	<p><?=lang('no_reorder_sets')?></p>

<?php else: ?>

	<table cellpadding="0" cellspacing="0" style="width:100%" class="mainTable" id="low-reorder-index">
		<colgroup>
			<col style="width:5%" />
			<col style="width:30%" />
			<col style="width:30%" />
			<col style="width:30%" />
			<col style="width:5%" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col">#</th>
				<th scope="col"><?=lang('set')?></th>
				<th scope="col"><?=lang('edit')?></th>
				<th scope="col"><?=lang('reorder')?></th>
				<th scope="col"><?=lang('delete')?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($sets AS $set) : ?>
				<tr class="<?=low_zebra()?>">
					<td><?=$set['set_id']?></td>
					<td><strong><?=htmlspecialchars($set['set_label'])?></strong></td>
					<td>
						<?php if ($set['can_edit']) : ?>
							<a href="<?=$base_url?>&amp;method=edit&amp;set_id=<?=$set['set_id']?>"><?=lang('edit_set')?></a>
						<?php else : ?>
							--
						<?php endif; ?>
					</td>
					<td>
						<?php if ($set['can_reorder']) : ?>
							<a href="<?=$base_url?>&amp;method=reorder&amp;set_id=<?=$set['set_id']?>"><?=lang('reorder_entries')?></a>
						<?php else : ?>
							--
						<?php endif; ?>
					</td>
					<td>
						<?php if ($set['can_edit']) : ?>
							<a href="<?=$base_url?>&amp;method=delete_confirm&amp;set_id=<?=$set['set_id']?>">
								<img src="<?=$themes_url?>cp_themes/default/images/icon-delete.png" alt="<?=lang('delete')?>" />
							</a>
						<?php else : ?>
							--
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<?php endif; ?>