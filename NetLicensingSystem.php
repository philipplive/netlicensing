<?php

class NetLicensingSystem extends HfCore\System {
	private ?NetLicensingAPI $api = null;

	public function __construct() {
		parent::__construct();

		// Api Endpunkte
		$this->getApi()->addEndpoint('cleancache', [$this, 'cleanCacheForce']);
		$this->getApi()->addEndpoint('update', [$this, 'updateSystem']);
	}

	/**
	 * Cashordner komplett leeren
	 */
	public function cleanCacheForce() {
		if (!$this->isAdmin())
			throw new Exception('Kein Zugriff', 403);

		$this->cleanCache('P0D', 10000);
	}

	/**
	 * Cache aufräumen
	 * @param string $maxAge
	 * @param int $maxCount Maximale Anzahl an Files welche pro Durchgang gelöscht werden
	 */
	public function cleanCache($maxAge = 'P7D', $maxCount = 20) {
		// Transients
		foreach ($this->getCacheController()->getAll() as $item)
			$this->getCacheController()->delete($item);

		// Files
		foreach (\HfCore\IO::getFolder($this->getPluginCachePath())->getFiles() as $file) {
			if ($file->getLastChange() < HfCore\Time::goBack($maxAge)) {
				$file->delete();

				if (--$maxCount < 0)
					return;
			}
		}
	}

	/**
	 * System Updaten
	 */
	public function updateSystem() {
		if (!$this->isAdmin())
			throw new Exception('Kein Zugriff', 403);

		$this->getGitHub()->update();
	}

	protected function getNetLicensingAPI(): NetLicensingAPI {
		if (!$this->api)
			$this->api = new NetLicensingAPI(get_option('netlicensing_api_key'));

		return $this->api;
	}

	/**
	 * @param string $id
	 * @return \NetLicensing\Licensee
	 * @throws Exception
	 */
	public function getLicenseeById(string $id): ?\NetLicensing\Licensee {
		$item = new \NetLicensing\Licensee($this->getNetLicensingAPI());
		try {
			$item->fetchIn($this->getNetLicensingAPI()->request(['licensee', $id], null, 'GET')['items']['item'][0]);
		}catch (Exception $ex){
			return null;
		}
		return $item;
	}

	public function getLicensees(): array {
		$items = [];

		foreach ($this->getNetLicensingAPI()->request(['licensee'], null, 'GET')['items']['item'] as $data) {
			$item = new \NetLicensing\Licensee($this->getNetLicensingAPI());
			$item->fetchIn($data);
			$items[] = $item;
		}

		return $items;
	}

	public function createLicensee(string $productNumber, string $number, bool $active = true) {
		$this->getNetLicensingAPI()->request('licensee', ['productNumber' => $productNumber, 'active' => $active, 'number' => $number]);
	}

}