<?php

namespace HfCore;

abstract class FileInfo {
	/**
	 * Pfad zur Datei
	 * @var string
	 */
	protected $path = null;

	/**
	 * Dateigrösse
	 * @var Bytes
	 */
	protected $size = null;

	/**
	 * Letzte Änderung
	 * @var DateTime
	 */
	protected $lastChange = null;

	/**
	 * Besitzer
	 * @var string
	 */
	protected $owner = null;

	/**
	 * Gruppe
	 * @var string
	 */
	protected $group = null;

	/**
	 * Berechtigungen
	 * @var int
	 */
	protected $chmod = null;

	/**
	 * Stream
	 * @var null
	 */
	protected $stream = null;

	public function __construct($path = null, $stream = null) {
		$this->stream = $stream;
		$this->setPath($path);
	}

	abstract public function getName();

	/**
	 * Pfad zur Datei
	 * @return string|string
	 */
	public function getPath(): ?string {
		return $this->path;
	}

	/**
	 * Pfad setzen
	 * @param string $path
	 */
	protected function setPath(?string $path) {
		$this->path = $this->fixPath($path);
	}

	/**
	 * Dateigrösse
	 * @param boolean $renew
	 * @return Bytes|null
	 */
	public function getSize(bool $renew = false): ?Bytes {
		if ($renew || $this->size === null)
			$this->setRAWInfo('size', $this->readRAWInfo('size'));

		return $this->size;
	}

	/**
	 * Letzte Änderung
	 * @param boolean $renew
	 * @return DateTime|null
	 */
	public function getLastChange(bool $renew = false): ?\DateTime {
		if ($renew || $this->lastChange === null)
			$this->setRAWInfo('lastChange', $this->readRAWInfo('lastChange'));

		return $this->lastChange;
	}

	/**
	 * Besitzer setzen
	 * @param string $owner
	 * @return $this
	 */
	public function setOwner(string $owner): self {
		$this->writeRAWInfo('owner', $owner);
		$this->setRAWInfo('owner', $owner);
		return $this;
	}

	/**
	 * Besitzer
	 * @param boolean $renew
	 * @return string|null
	 */
	public function getOwner(bool $renew = false): ?string {
		if ($renew || $this->owner === null)
			$this->setRAWInfo('owner', $this->readRAWInfo('owner'));

		return $this->owner;
	}

	/**
	 * Gruppe setzen
	 * @param string $group
	 * @return $this
	 */
	public function setGroup(string $group): self {
		$this->writeRAWInfo('group', $group);
		$this->setRAWInfo('group', $group);
		return $this;
	}

	/**
	 * Gruppe
	 * @param boolean $renew
	 * @return string|null
	 */
	public function getGroup(bool $renew = false): ?string {
		if ($renew || $this->group === null)
			$this->setRAWInfo('group', $this->readRAWInfo('group'));

		return $this->group;
	}

	/**
	 * Chmod setzen
	 * @param string|int $chmod z.B. 0755
	 * @return $this
	 */
	public function setChmod($chmod) {
		$this->writeRAWInfo('chmod', $chmod);
		$this->setRAWInfo('chmod', $chmod);
		return $this;
	}

	/**
	 * Chmod
	 * @param boolean $renew
	 * @return int|null
	 */
	public function getChmod($renew = false): ?int {
		if ($renew || $this->chmod === null)
			$this->setRAWInfo('chmod', $this->readRAWInfo('chmod'));

		return $this->chmod;
	}

	/**
	 * Chmod als String
	 * @return string
	 */
	public function getChmodAsString(): string {
		return sprintf('%04d', $this->getChmod());
	}

	/**
	 * Existiert die Datei oder Ordner?
	 * @return boolean
	 */
	public function exists(): bool {
		throw new NotImplementedException();
	}

	/**
	 * FileStream
	 * @return mixed
	 */
	public function getStream() {
		return $this->stream;
	}

	public function setRAWInfo($name, $value) {
		switch ($name) {
			case 'size':
				if ($value instanceof Bytes)
					$this->size = Bytes::create($value->value);
				else
					$this->size = Bytes::create($value);
				break;

			case 'lastChange':
				if ($value instanceof DateTime)
					$this->lastChange = $value;
				else if (is_int($value))
					$this->lastChange = Time::fromTimestamp($value);
				else
					$this->lastChange = null;
				break;

			case 'owner':
			case 'group':
				$this->$name = $value;
				break;

			case 'chmod':
				if (is_numeric($value))
					$this->chmod = $value;
				else if (is_string($value))
					$this->chmod = self::convertChmod($value);
				else
					$this->chmod = null;
				break;

			default:
				throw new SystemException(sprintf('Unbekannte Eigenschaft %s', $name));
		}
		return $this;
	}

	public function __toString() {
		return $this->getPath();
	}

	protected function readRAWInfo($name) {
		throw new NotImplementedException();
	}

	protected function writeRAWInfo($name, $value) {
		throw new NotImplementedException();
	}

	/**
	 * Konvertiert CHMOD
	 * @param string $mode z.B. dr--r--r--
	 * @return string z.B. 0644
	 */
	public static function convertChmod(string $mode): string {
		$s = 1;
		$spez = 0;
		$chmod = 0;

		for ($i = 9; $i > 0; $i -= 3) {
			if ($mode[$i] == 'x' || $mode[$i] == 't' || $mode[$i] == 's')
				$chmod += 1;
			if ($mode[$i - 1] == 'w')
				$chmod += 2;
			if ($mode[$i - 2] == 'r')
				$chmod += 4;

			if (strtolower($mode[$i]) == 't' || strtolower($mode[$i]) == 's')
				$spez += $s;

			$chmod *= 10;
			$s *= 2;
		}

		return strrev($chmod + $spez);
	}

	/**
	 * Prüfe Dateiname / Ordnername
	 * @param string $name
	 * @throws NotImplementedException
	 */
	public static function checkName(?string $name) {
		throw new NotImplementedException();
	}

	/**
	 * Normalisiere Pfad
	 * @param string $path
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function fixPath(?string $path): ?string {
		throw new NotImplementedException();
	}

}
