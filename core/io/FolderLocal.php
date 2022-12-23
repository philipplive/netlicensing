<?php
namespace HfCore;

/**
 * Class FolderLocal
 * @method FolderLocal getFolder(string $foldername)
 * @method FileLocal getFile(string $filename)
 * @method FolderLocal getParentFolder()
 * @method FolderLocal getClone()
 */
class FolderLocal extends FolderAbstract {
	/**
	 * Existiert Ordner?
	 * @return boolean
	 */
	public function exists(): bool {
		return is_dir($this->getPath());
	}

	/**
	 * Dateien auslesen
	 * @return FileLocal[]
	 */
	public function getFiles() {
		$items = [];
		foreach ($this->getFilesAndFolders() as $item) {
			if ($item instanceof FileAbstract)
				$items[] = $item;
		}
		return $items;
	}

	/**
	 * Ordner auslesen
	 * @return FolderLocal[]
	 */
	public function getFolders() {
		$items = [];
		foreach ($this->getFilesAndFolders() as $item) {
			if ($item instanceof FolderAbstract)
				$items[] = $item;
		}
		return $items;
	}

	/**
	 * Dateien und Ordner auslesen
	 * @return FileLocal[]|FolderLocal[]
	 * @throws Exception
	 */
	public function getFilesAndFolders() {
		$dh = opendir($this->getPath());
		if (!$dh)
			throw new Exception('Fehler beim Öffnen von Verzeichnis');

		$items = [];
		while (($file = readdir($dh)) !== false) {
			if ($file == '.' || $file == '..')
				continue;

			if (is_dir($this->getPath().$file))
				$items[] = $this->getFolder($file);
			else
				$items[] = $this->getFile($file);
		}
		closedir($dh);

		return $items;
	}

	/**
	 * Verpackt den Inhalt des Ordners (inkl. sich selbst)
	 * @param string $zipFile
	 * @return FileLocal
	 */
	public function addToZip($zipFile) {
		$sourcePath = self::fixPath($this->getPath(), false);
		$z = new \ZipArchive();
		$z->open($zipFile, \ZipArchive::CREATE);
		self::folderToZip($sourcePath, $z, strlen($sourcePath) + 1);
		$z->close();
		return IO::getFile($zipFile);
	}

	protected function createFolder() {
		if (!mkdir($this->getPath()))
			throw new Exception(sprintf('Ordner "%s" konnte nicht erstellt werden', $this->getPath()));
	}

	protected function deleteFolder() {
		if (!rmdir($this->getPath()))
			throw new Exception(sprintf('Ordner "%s" konnte nicht gelöscht werden', $this->getPath()));
	}

	protected function renameFolder($newname, $newpath) {
		if (!rename($this->getPath(), $newpath))
			throw new Exception(sprintf('Ordner "%s" konnte nicht umbenannt werden', $this->getPath()));
	}

	protected function readRAWInfo($name) {
		switch ($name) {
			case 'size':
				return stat($this->getPath())['size'];
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
					throw new Exception(sprintf('Fehler bei Chmod von Ordner "%s"', $this->getPath()));

				return;

			case 'owner':
				if (!chown($this->getPath(), $value))
					throw new Exception(sprintf('Fehler bei Chown von Ordner "%s"', $this->getPath()));

				return;

			case 'group':
				if (!chgrp($this->getPath(), $value))
					throw new Exception(sprintf('Fehler bei Chgrp von Ordner "%s"', $this->getPath()));

				return;
		}
		parent::writeRAWInfo($name, $value);
	}

	/**
	 * Add files and sub-directories in a folder to zip file.
	 * @param string $folder
	 * @param ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusived from the file path.
	 */
	protected static function folderToZip($folder, \ZipArchive &$zipFile, $exclusiveLength) {
		$handle = opendir($folder);

		if (!$handle)
			throw new \Exception('Handle ist null, Fehlt der Autoinstall-Ordner?');

		while (false !== $f = readdir($handle)) {
			if ($f != '.' && $f != '..') {
				$filePath = $folder.'/'.$f;
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath))
					$zipFile->addFile($filePath, $localPath);
				else if (is_dir($filePath)) {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					self::folderToZip($filePath, $zipFile, $exclusiveLength);
				}
			}
		}
		closedir($handle);
	}

}
