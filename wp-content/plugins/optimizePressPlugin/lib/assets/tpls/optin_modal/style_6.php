<?php include 'style.inc.php'; ?>

<div id="<?php echo $id; ?>" class="optin-box optin-box-5 cf"<?php echo $style_str; ?>>
	<div class="optin-box-content">
		<?php
			$headline = op_get_var($content,'headline','','<h2>%1$s</h2>');
			echo !empty($headline) ? $headline : '';
			
			$paragraph = op_get_var($content,'paragraph','');
			echo !empty($paragraph) ? '<p class="description">'.strip_tags($paragraph).'</p>' : '';
		?>
	</div>

	<?php echo $form_open.$hidden_str ?>
	<div class="text-boxes">
	<?php 
		op_get_var_e($fields,'email_field');
		echo implode('',$extra_fields);
	?>
	</div>
	<?php echo $submit_button ?>
	</form>
	<?php op_get_var_e($content,'privacy','','<p class="privacy"><img src="'.OP_ASSETS_URL.'images/optin_box/privacy.png" alt="privacy" width="16" height="15" /> %1$s</p>') ?>
</div>