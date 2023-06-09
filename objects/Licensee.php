<?php

namespace NetLicensing;

class  Licensee extends ApiObject {
	/**
	 * Lizenznehmer Id
	 * @var string
	 */
	public string $number;

	/**
	 * Produktnummer
	 * @var string
	 */
	public string $productNumber;

	/**
	 * Aktiv?
	 * @var bool
	 */
	public bool $active;

	public function fetchIn(array $data) {
		$this->number = $data['property'][0]['value'];
		$this->active = $data['property'][1]['value'] == 'true';
		$this->productNumber = $data['property'][2]['value'];
	}

	public function getShopURL(): string {
		return $this->api->request('token', ['tokenType' => 'SHOP', 'licenseeNumber' => $this->number])['items']['item'][0]['property'][4]['value'];
	}

	/**
	 * @return array
	 */
	public function getLicences() : array {
		throw new \Exception('Not implementet');
		$items = [];

		foreach ($this->api->request(['licensee',$this->number,'validate'], [],'POST')['items']['item'] as $item){
		}

		return $items;
	}
}
