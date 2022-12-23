<?php
namespace HfCore;

class GitHub {
	private $system = null;

	public function __construct(System $system) {
		$this->system = $system;
	}

	public function getLocalHeader(): array {
		$file = IO::readFile($this->system->getPluginPath().$this->system->getPluginName().'.php');

		return $this->parseHeader($file);
	}

	public function getMasterHeader(): array {
		$name = $this->githubApiRequest(['repositories', $this->getLocalHeader()['githubid']])->full_name;
		$file = CurlClient::create(sprintf('https://raw.githubusercontent.com/%s/master/%s.php', $name, $this->system->getPluginName()))->exec()->getBody();

		return $this->parseHeader($file);
	}

	/**
	 * Headerbereich parsen
	 * @param string $file
	 * @return array
	 */
	private function parseHeader(string $file): array {
		$vars = [];
		$output = null;
		preg_match_all('/^\s\*\s(.*)$/m', $file, $output);

		foreach ($output[1] as $line) {
			$parts = preg_split('/\t+/', $line);

			if (count($parts) == 2)
				$vars[strtolower(str_replace([':', ' '], '', trim($parts[0])))] = trim($parts[1]);
		}

		return $vars;
	}

	private function downloadMasterZip(): FileLocal {
		$data = $this->githubApiRequest(['repositories', $this->getLocalHeader()['githubid']]);
		$url = sprintf('https://codeload.github.com/%s/zip/refs/heads/master', $data->full_name);

		return IO::getFile($this->system->getPluginCachePath().'master.zip')->write(CurlClient::create($url)->exec()->getBody());
	}

	/**
	 * Instanz updaten
	 */
	public function update() {
		$this->checkUpdateFunctionality();

		if ($this->isUpToDate())
			throw new \Exception('Version bereits aktuell');

		$file = $this->downloadMasterZip();

		$tmp = IO::getFolder($this->system->getPluginCachePath('update'));

		// Entpacken
		$zip = new \ZipArchive;
		if ($zip->open($file->getPath()) === true) {
			$zip->extractTo($tmp);
			$zip->close();
		}
		else {
			throw new \Exception('Fehler beim entpacken');
		}

		// Verschieben und ersetzen
		$tmp->getClone()->cd($this->githubApiRequest(['repositories', $this->getLocalHeader()['githubid']])->name.'-master')->copy(IO::getFolder($this->system->getPluginPath()));

		// Tmp löschen
		$tmp->getParentFolder()->clear();

		// Cache leeren
		foreach ($this->system->getCacheController()->getAll() as $item)
			$this->system->getCacheController()->delete($item);
	}

	/**
	 * Prüfe ob Update möglich
	 * @return bool
	 */
	public function checkUpdateFunctionality(): bool {
		if (!class_exists('\ZipArchive'))
			throw new \Exception('ZipArchive nicht vorhanden');

		return true;
	}

	/**
	 * Request zur GitHub API
	 * @param array $query
	 * @param int|null $maxAge in Sekunden
	 * @return object
	 */
	public function githubApiRequest(array $query, ?int $maxAge = null) {
		$id = md5(implode('|', $query));

		if ($maxAge) {
			$result = $this->system->getCacheController()->get($id);

			if ($result)
				return $result;
		}

		$result = CurlClient::create('https://api.github.com/'.implode('/', $query))->setUserAgent('User-Agent: WordPress')->exec()->getFromJSON();

		if ($maxAge)
			$this->system->getCacheController()->set($id, $result, $maxAge);

		return $result;
	}

	/**
	 * Version
	 * @param bool $localInstance
	 * @return string
	 */
	public function getVersion(bool $localInstance = true): string {
		if ($localInstance)
			return $this->getLocalHeader()['version'];
		else
			return $this->getMasterHeader($localInstance)['version'];
	}

	/**
	 * Ist die aktuelle Instanz aktuell?
	 * @return bool
	 */
	public function isUpToDate(): bool {
		$value = $this->system->getCacheController()->get('upToDate');

		if ($value === false) {
			$value = ($this->getVersion() == $this->getVersion(false)) ? 1 : 0;
			$this->system->getCacheController()->set('upToDate', $value, 360);
		}

		return (bool)$value;
	}
}