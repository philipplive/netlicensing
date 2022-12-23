<?php

namespace NetLicensing;

class  Licensee extends ApiObject {
	/**
	 * Kunden ID
	 * @var string
	 */
	public string $number;

	/**
	 * Produktnummer
	 * @var string
	 */
	public string $productNumber;


	public function fetchIn(array $data) {
		$this->number = $data['property'][0]['value'];
		$this->active = $data['property'][1]['value'] == 'true';
		$this->productNumber = $data['property'][2]['value'];
	}

	public function getShopURL(): string {
		return $this->api->request('token', ['tokenType' => 'SHOP', 'licenseeNumber' => $this->number])['items']['item'][0]['property'][4]['value'];
	}
}