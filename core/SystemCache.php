<?php
namespace HfCore;

/**
 * Cache
 */
class SystemCache {
	/**
	 * @var string
	 */
	private $identifier;

	public function __construct($identifier) {
		if(empty($identifier))
			throw new \Exception('Cache-Identifier muss gesetzt werden');

		$this->identifier = $identifier;
	}

	public function get(string $name) {
		return get_transient($this->identifier.'_'.$name);
	}

	public function set(string $name, $data, $maxAge = 3600) {
		set_transient($this->identifier.'_'.$name, $data, $maxAge);
	}

	public function delete(string $name){
		delete_transient($this->identifier.'_'.$name);
	}

	public function getAll() : array{
		global $wpdb;
		$data = [];

		foreach ($wpdb->get_results("SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%_transient_$this->identifier%'",ARRAY_A) as $result)
			$data[] = trim(str_replace('_transient_'.$this->identifier.'_','',array_pop($result)));

		return $data;
	}
}
