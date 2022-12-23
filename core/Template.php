<?php
namespace HfCore;

/**
 * Template Abstract
 */
class Template {
	public $cssFiles = [];
	public $jsFiles = [];
	public $system;

	public function __construct(System $system) {
		add_action('admin_enqueue_scripts', [$this, 'drawScripts']);
		add_action('wp_enqueue_scripts', [$this, 'drawScripts']);

		$this->system = $system;
	}

	/**
	 * CSS-Files zur aktuellen Ausgabe hinzufügen (less wird geparst)
	 * @param $file z.B. tpl/test.less
	 */
	public function addCssFile(string $file): self {
		$fileOut = str_replace('/', '-', $file);
		$fileOut = str_replace('.less', '.css', $fileOut);

		$less = new \hf_lessc();
		$less->checkedCompile($this->system->getPluginPath().$file, System::getInstance()->getPluginCachePath().$fileOut);

		$this->cssFiles[] = $fileOut;

		return $this;
	}

	/**
	 * JS-Files zur aktuellen Ausgabe hinzufügen
	 * @param $file z.B. tpl/test.js
	 */
	public function addJsFile(string $file): self {
		$this->jsFiles[] = $file;

		return $this;
	}

	public function drawScripts() {
		foreach ($this->cssFiles as $file)
			wp_enqueue_style($this->system->getPluginName().'-'.str_replace('.', '-', $file), System::getInstance()->getPluginCacheUrl().$file);

		foreach ($this->jsFiles as $file)
			wp_enqueue_script($this->system->getPluginName().'-'.str_replace('.', '-', $file), System::getInstance()->getPluginUrl().$file);
	}

	/**
	 * String HTML escape
	 * @param string|null $str string
	 * @return string|null Escaped String
	 */
	public static function escape(?string $str): ?string {
		if ($str === null)
			return null;

		return htmlspecialchars($str, (defined('ENT_HTML5') ? ENT_HTML5 : 0) | ENT_COMPAT, 'UTF-8');
	}

	/**
	 * String HTML un-escape
	 * @param string|null $str
	 * @return string|null
	 */
	public static function unescape(?string $str): ?string {
		if ($str === null)
			return null;

		return html_entity_decode($str, (defined('ENT_HTML5') ? ENT_HTML5 : 0) | ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Notizbox anzeigen auf jeder Backend-Seite
	 * @param string $txt
	 * @param int $severity
	 */
	public static function addAdminNoticeBox(string $txt, int $severity = \Severity::WARNING) {
		add_action('admin_notices', function () use ($txt,$severity) {
			$class = 'notice';

			if($severity == \Severity::WARNING)
				$class .= ' notice-warning';
			if($severity == \Severity::ERROR)
				$class .= ' notice-error';
			if($severity == \Severity::SUCCESS)
				$class .= ' notice-success';

			printf('<div class="%1$s"><p>%2$s</p></div>', $class, $txt);
		});
	}
}
