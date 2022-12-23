<?php

namespace HfCore;

/**
 * Class FileLocal
 * @method FolderLocal getFolder()
 */
class FileLocal extends FileAbstract {
	/**
	 * Existiert Datei?
	 * @return boolean
	 */
	public function exists(): bool {
		return file_exists($this->getPath());
	}

	protected function deleteFile() {
		if (!unlink($this->getPath()))
			throw new Exception(sprintf('Datei "%s" konnte nicht gelöscht werden', $this->getPath()));
	}

	protected function touchFile(?\DateTime $time = null) {
		if ($time === null)
			$time = new DateTime();

		if (!touch($this->getPath(), $time->getTimestamp()))
			throw new Exception(sprintf('Fehler beim Touch von Datei "%s"', $this->getPath()));
	}

	protected function renameFile($newname, $newpath) {
		if (!rename($this->getPath(), $newpath))
			throw new Exception(sprintf('Datei "%s" konnte nicht umbenannt werden', $this->getPath()));
	}

	protected function writeContent($content) {
		if (!$stream = fopen($this->getPath(), 'w+'))
			throw new Exception(sprintf('Fehler beim Öffnen der Datei "%s"', $this->getPath()));

		if (fwrite($stream, $content) === false)
			throw new Exception(sprintf('Fehler beim Schreiben der Datei "%s"', $this->getPath()));

		fclose($stream);
	}

	protected function readContent() {
		$data = file_get_contents($this->getPath());

		if ($data === false)
			throw new Exception(sprintf('Fehler beim Öffnen der Datei "%s"', $this->getPath()));

		return $data;
	}

	protected function copyFile(FileAbstract $target, $replace = false) {
		if ($target->getStream() instanceof \FTP)
			$target->getStream()->uploadFile($this->getPath(), $target->getPath());
		else
			parent::copyFile($target, $replace);
	}

	protected function readRAWInfo($name) {
		switch ($name) {
			case 'size':
				return filesize($this->getPath());
			case 'lastChange':
				return stat($this->getPath())['mtime'];
			case 'chmod':
				return (int)substr(sprintf('%o', fileperms($this->getPath())), -4);
			case 'group':
				return filegroup($this->getPath());
			case 'owner':
				return fileowner($this->getPath());
		}
		return parent::readRAWInfo($name);
	}

	protected function writeRAWInfo($name, $value) {
		switch ($name) {
			case 'chmod':
				if (!chmod($this->getPath(), octdec(sprintf('%04d', $value))))
					throw new Exception(sprintf('Fehler bei Chmod von Datei "%s"', $this->getPath()));

				return;

			case 'owner':
				if (!chown($this->getPath(), $value))
					throw new Exception(sprintf('Fehler bei Chown von Datei "%s"', $this->getPath()));

				return;

			case 'group':
				if (!chgrp($this->getPath(), $value))
					throw new Exception(sprintf('Fehler bei Chgrp von Datei "%s"', $this->getPath()));

				return;
		}
		parent::writeRAWInfo($name, $value);
	}

}
