<?php

namespace NetLicensing;

class  ApiObject {
	public \NetLicensingAPI $api;

	public function __construct(\NetLicensingAPI $api) {
		$this->api = $api;
	}
}