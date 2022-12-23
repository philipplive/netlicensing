<div class="wrap">
	<h2>NetLicensing Plugin</h2>

	<form action="options.php" method="post">
		<?php settings_fields('netlicensing_settings'); ?>
		<?php do_settings_sections('netlicensing_settings'); ?>

		<input name="Submit" type="submit"
			   value="Einstellungen speichern"
			   class="button button-primary"/>
	</form>

	<hr>

	<h2>Weiteres...</h2>
	<?php do_settings_sections('netlicensing_debug'); ?>

	<p>Cache sofort leeren (wird ansonsten automatisch jede Woche geleert)</p>
	<form action="" method="post">
		<input name="Submit" type="submit"
			   value="Cache leeren"
			   onclick="HfCore.request('netlicensing/cleancache');"
			   class="button button-primary"/>
	</form>
</div>