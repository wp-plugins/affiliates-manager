<?php
	$ICON_EDIT = WPAM_URL . "/images/icon_edit.png";
 	$ICON_DELETE = WPAM_URL . "/images/icon_delete.png";
?>

<script src="<?php echo WPAM_URL?>/js/jquery.tablednd_0_5.js" type="text/javascript"></script>
<script type="text/javascript">

	jQuery(function($) {
		
		$("#tabs").tabs({
			cookie: {
				  name: 'wpam_settings_tab'
			}
		});

	});
</script>

<div class="wrap">

	<h2>Settings</h2>
	<?php if (isset($this->viewData['updateMessage'])) {?>
		<div id="updated" class="updated">
			<p><?php echo $this->viewData['updateMessage']?></p>
		</div>
	<?php }?>

<?php
require_once WPAM_BASE_DIRECTORY . "/html/widget_form_errors_panel.php";
?>

	<form method="post" action="admin.php?page=wpam-settings">
		<input type="hidden" name="action" value="submitSettings" />

		<div id="tabs">
			<ul>
				<li><a href="#tabs-general">General</a></li>
				<li><a href="#tabs-affiliate-registration">Affiliate Registration</a></li>
				<li><a href="#tabs-messaging">Messaging</a></li>
				<li><a href="#tabs-payment">Payment</a></li>
			</ul>
			<div id="tabs-general">
				<?php require_once "settings_general.php" ?>
			</div>

			<div id="tabs-affiliate-registration">
				<?php require_once "settings_registration.php" ?>
			</div>
			<div id="tabs-messaging">
				<?php require_once "settings_messaging.php" ?>
			</div>

			<div id="tabs-payment">
				<?php require_once "settings_payment.php" ?>
			</div>
		</div>

		<div style="margin: 10px;">
			<input class="button-primary" type="submit" name="btnSubmit" id="btnSubmit" value="Save Settings" />
		</div>



	</form>
</div>