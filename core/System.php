<?php
namespace HfCore;

class System {
	/**
	 * Aktuelle Systeminstanzen.
	 * Sind mehrere Plugins aktiv welche den HFCore verwenden, so wird jede Systeminstanz hier separat instanziert
	 * @var System[]
	 */
	protected static $instance = [];

	/**
	 * @var Cronjob
	 */
	private $cronjobs = null;

	/**
	 * @var Template
	 */
	private $template = null;

	/**
	 * @var Api
	 */
	private $api = null;

	/**
	 * @var SystemCache
	 */
	private $cache = null;

	/**
	 * @var GitHub
	 */
	private $github = null;

	/**
	 * Name des Plugins
	 * @var string
	 */
	private $pluginName;

	public function __construct() {
		if (count(self::$instance))
			throw new \Exception('System-Instanz existiert bereits');

		// Datentypen
		define('HFCore\T_BOOL', 'b');
		define('HFCore\T_INT', 'i');
		define('HFCore\T_DOUBLE', 'd');
		define('HFCore\T_STR', 's');
		define('HFCore\T_ARR', 'a');

		// Core-Klassen laden
		require_once('Query.php');
		require_once('Severity.php');
		require_once('Price.php');
		require_once('Time.php');
		require_once('Image.php');
		require_once('Xml.php');
		require_once('Color.php');
		require_once('HtmlNode.php');
		require_once('Validator.php');
		require_once('Image.php');
		require_once('SystemCache.php');
		require_once('io/IO.php');
		require_once('io/FileInfo.php');
		require_once('io/FileAbstract.php');
		require_once('io/FileLocal.php');
		require_once('io/FolderAbstract.php');
		require_once('io/FolderLocal.php');
		require_once('CurlClient.php');

		// Libs
		require_once('libs/lessc.inc.php');

		// Plugin Name
		$this->pluginName = explode('/',explode('/wp-content/plugins/',__FILE__)[1])[0];

		// Instanz übertragen
		self::$instance[$this->getPluginName()] = $this;
	}

	/**
	 * Gibt Instanz des Systems zurück
	 * @return static
	 */
	public static function getInstance(): self {
		$pluginName = explode('/',explode('/wp-content/plugins/',__FILE__)[1])[0];

		if (!isset(self::$instance[$pluginName]))
			die('System nicht instanziert');

		return self::$instance[$pluginName];
	}

	public function getCronjobController(): Cronjob {
		if (!$this->cronjobs) {
			require_once('Cronjob.php');
			$this->cronjobs = new Cronjob();
		}

		return $this->cronjobs;
	}

	public function getApi(): Api {
		if (!$this->api) {
			require_once('Api.php');
			$this->api = new Api($this);
		}

		return $this->api;
	}

	public function getGitHub(): GitHub {
		if (!$this->github) {
			require_once('GitHub.php');
			$this->github = new GitHub($this);
		}

		return $this->github;
	}

	/**
	 * Widget registrieren
	 * @param string $name
	 */
	public function addWidget(string $name) {
		require_once($this->getPluginPath().'/'.$name.'.php');

		add_action('widgets_init', function () use ($name) {
			register_widget($name);
		});

	}

	public function getTemplateController(): Template {
		if (!$this->template) {
			require_once('Template.php');
			$this->template = new Template($this);
			$this->template->addJsFile('core/system.js');
		}

		return $this->template;
	}

	public function getCacheController(): SystemCache {
		if (!$this->cache) {
			$this->cache = new SystemCache($this->getPluginName());
		}

		return $this->cache;
	}

	/**
	 * Name es Plugins
	 * @return string
	 */
	public function getPluginName(): string {
		return $this->pluginName;
	}

	/**
	 * @return string Serverpfad /srv/wwww/blabla/....
	 */
	public function getPluginPath(): string {
		return WP_PLUGIN_DIR.'/'.$this->getPluginName().'/';
	}

	/**
	 * Pfad zum Cache-Ordner
	 * @param string $folder Cache Unterordner wählen
	 * @return string  /srv/wwww/blabla/..../cache/
	 */
	public function getPluginCachePath(string $folder = ''): string {
		if ($folder && substr($folder, -1) != '/')
			$folder .= '/';

		return $this->getPluginPath().'/cache/'.$folder;
	}

	/**
	 * Pfad zum Cache-Ordner
	 * @param string $folder Cache Unterordner wählen
	 * @return string  www.test.ch/blabla....
	 */
	public function getPluginCacheUrl(string $folder = ''): string {
		if ($folder && substr($folder, -1) != '/')
			$folder .= '/';

		return $this->getPluginUrl().'cache/'.$folder;
	}

	/**
	 * @return string www.test.ch/blabla....
	 */
	public function getPluginUrl(): string {
		return get_site_url().'/wp-content/plugins/'.$this->getPluginName().'/';
	}

	/**
	 * Aktuelle BenutzerID
	 * @return int
	 * @throws \Exception
	 */
	public function getCurrentUserId(): int {
		$userId = wp_validate_logged_in_cookie(false);

		if ($userId === false)
			throw new \Exception('User nicht eingeloggt');

		return $userId;
	}

	/**
	 * @return bool
	 */
	public function isAdmin(): bool {
		try {
			$user = get_userdata($this->getCurrentUserId());

			if (in_array('administrator', $user->roles))
				return true;
		} catch (\Exception $ex) {
			// Nicht eingeloggt
		}

		return false;
	}
}