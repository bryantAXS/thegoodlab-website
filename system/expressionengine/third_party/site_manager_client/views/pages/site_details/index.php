<?php echo $navigation_top ?>
<div class="columns nav">
	<div class="left">
		<ul class="top">
		<?php foreach ($navigation as $nav) : ?>
			<li class="<?php if($nav['active']) echo "active"; ?>"><a href="<?php echo $nav['url'] ?>"><?php echo $nav['label'] ?></a></li>
		<?php endforeach; ?>
		</ul>

		<ul class="bottom">
			<li><a href="<?php echo $delete_url ?>" onclick="return confirm('Are you sure you want to delete this site?');">Delete Site</a></li>
		</ul>
	</div>
	<div class="right">
		<div class="site-details">
			<h1 class="mainHeading"><?php echo $site->name() ?></h1>
			<div class="statusBlock">
				<span class="status live"></span>
				<span class="status connecting active"></span>
				<span class="status offline"></span>
			</div>

			<p class="meta"><a href="<?php echo $site->base_url() ?>" target="_blank"><?php echo $site->base_url() ?></a> &nbsp; &nbsp; &nbsp; &nbsp; <a href="<?php echo $site->login_url() ?>" target="_blank">Login to CP</a> &nbsp; &nbsp; &nbsp; &nbsp; EE: <span class="app_version">-</span></p>

			<div class="dynamicDataWrapper">
				<div class="dynamicData">
					<table class="mainTable padTable statTable" border="0" cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<th width="25%">License Number</th>
								<td width="25%" class="license_number"></td>
							</tr>
							<tr>
								<th>System Online</th>
								<td class="is_system_on"></td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>


<script type="text/javascript">
	window.SM = {};
	window.SM.XID = "<?php echo $XID ?>";
	window.SM.js_api = "<?php echo $js_api ?>";
	window.SM.js_decryption_api = "<?php echo $js_decryption_api ?>";
	window.SM.js_encryption_api = "<?php echo $js_encryption_api ?>";
	window.SM.site_id = <?php echo $site->id() ?>;

	define('site_config', [], function() {
		return <?php echo $site->js_config() ?>;
	});

</script>