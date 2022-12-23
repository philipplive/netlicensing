<?php

namespace NetLicensing;

class  Product extends ApiObject {
	/**
	 * Product Id
	 * @var string
	 */
	public string $number;

	/**
	 * Aktiv?
	 * @var bool
	 */
	public bool $active;

	public string $name;

	public string $version;

	public string $description;

	public function fetchIn(array $data) {
		$this->number = $data['property'][0]['value'];
		$this->active = $data['property'][1]['value'] == 'true';
		$this->name = $data['property'][2]['value'];
		$this->version = $data['property'][3]['value'];
		$this->description = $data['property'][5]['value'];
	}
}