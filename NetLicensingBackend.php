<?php

class NetLicensingBackend extends NetLicensingSystem {

	public function __construct() {
		parent::__construct();
		add_action('admin_menu', [$this, 'addOptionPage']);
		add_action('admin_init', [$this, 'adminInit']);

		$this->getTemplateController()->addCssFile('tpl/backend.less');
		$this->getTemplateController()->addJsFile('tpl/cycly.js');
		$this->getTemplateController()->addJsFile('tpl/backend.js');
	}

	/**
	 * Eintrag im Hauptmenü
	 */
	public function addOptionPage() {
		add_menu_page(
			'NetLicensing Optionen',
			//$this->getGitHub()->isUpToDate() ? 'NetLicensing' : 'NetLicensing <span class="update-plugins">!</span>',
			'NetLicensing',
			'manage_options',
			'netlicensing',
			function () {
				include('tpl/tpl_settings.php');
			},
			'dashicons-external', 100
		);
	}

	public function adminInit() {
	// Einstellungen API
		add_settings_section(
			'netlicensing_settings_section',
			'API Einstellungen',
			function () {
				echo HfCore\HtmlNode::p('Verbindungseinstellungen für die REST-API');
			},
			'netlicensing_settings'
		);

		add_settings_field(
			'netlicensing_api_key',
			'NetLicensing API Key',
			function () {
				echo HfCore\HtmlNode::input()->attr('name', 'netlicensing_api_key')->attr('type', 'text')->addClass('large')->value(get_option('netlicensing_api_key'));
			},
			'netlicensing_settings',
			'netlicensing_settings_section'
		);

		register_setting('netlicensing_settings', 'netlicensing_api_key');

		// Debug
		add_settings_section(
			'netlicensing_debug',
			'',
			function () {
				echo '
				<div class="card" style="opacity: 0.5;">
				<h3>Debug</h3>
					<p>Files im Cache: '.count(\HfCore\IO::getFolder(\HfCore\System::getInstance()->getPluginCachePath())->getFiles()).'</p>
					<p>WP Transients: '.count(\HfCore\System::getInstance()->getCacheController()->getAll()).'</p>
				</div>
				';
			},
			'netlicensing_debug'
		);
	}
}