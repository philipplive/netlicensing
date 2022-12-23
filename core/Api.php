<?php
namespace HfCore;

class Api {
	private $system = null;

	public function __construct(System $system) {
		$this->system = $system;
	}

	/**
	 * Wordpress API Endpunkt hinzufügen
	 * Z.B. http://test.ch/wp-json/pluginname/methodenname/12
	 * @param $method methodenname/(?P<id>\d+)
	 * @param $callback [$this,'methodenname']
	 */
	public function addEndpoint(string $method, $callback) {
		add_action('rest_api_init', function () use ($callback, $method) {
			register_rest_route($this->system->getPluginName().'/', $method, array(
				'methods' => 'GET,POST',
				'callback' => $callback
			));
		});
	}
}