<?php

/**
 * @doc https://netlicensing.io/wiki/restful-api
 */
class NetLicensingAPI {
	public string $key = '';

	public function __construct(string $key) {
		$this->key = $key;
	}

	public function request($method = 'licensee', ?array $parameters = null, string $type = 'POST') {
		if (is_array($method))
			$method = implode('/', $method);

		$curl = \HfCore\CurlClient::create('https://go.netlicensing.io/core/v2/rest/'.$method);
		$curl->setBasicAuth('apiKey', $this->key);

		if (!empty($parameters)) {
			// Format
			foreach ($parameters as $key => $value){
				if(is_bool($parameters[$key]))
					$parameters[$key] = $value ? 'true' : 'false';
			}

			$curl->setPOSTData(http_build_query(
				$parameters
			));
		}

		$curl->setHTTPHeader('Content-Type: application/x-www-form-urlencoded');
		$curl->setHTTPHeader('Accept: application/json');

		if ($type == 'POST')
			$curl->setMethodPOST();

		$res = $curl->exec();

		if ($res->httpCode == 401)
			throw new Exception('Schlüssel ungültig');
		else if ($res->httpCode == 400)
			throw new Exception('Anfrage ungültig');

		return $res->getFromJSON(true);
	}
}

