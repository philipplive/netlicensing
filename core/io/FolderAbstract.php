<?php
namespace HfCore;

abstract class FolderAbstract extends FileInfo {
	/**
	 * Ordnername
	 * @return string
	 */
	public function getName() {
		return pathinfo($this->getPath(), PATHINFO_BASENAME);
	}

	/**
	 * Gibt Pfad des übergeordneten Ordners zurück
	 * Ist der ordner bereits zuunterst, wird \/ oder ./ zurückgegeben
	 * @return string
	 */
	public function getParentPath() {
		return self::fixPath(pathinfo($this->getPath(), PATHINFO_DIRNAME));
	}

	/**
	 * Ordner erstellen
	 * @return $this
	 */
	public function create() {
		$this->createFolder();
		return $this;
	}

	/**
	 * Ordner erstellen, falls dieser nicht existiert
	 * @param int $recursive Anzahl Parent-Ordner rekursiv erstellen
	 * @return $this
	 */
	public function createIfNotExists($recursive = 0) {
		if (!$this->exists()) {
			if ($recursive)
				$this->getParentFolder()->createIfNotExists($recursive - 1);

			new Log(sprintf('Erstelle nicht existierenden Ordner "%s"', $this->getPath()));
			$this->create();
		}

		return $this;
	}

	/**
	 * Ordner löschen
	 * @return $this
	 */
	public function delete() {
		$this->deleteFolder();
		return $this;
	}

	/**
	 * Dateien auslesen
	 * @return FileAbstract[]
	 */
	public function getFiles() {
		throw new NotImplementedException();
	}

	/**
	 * Datei in Ordner öffnen
	 * @param string $filename
	 * @return FileAbstract
	 */
	public function getFile($filename) {
		return IO::getFile($this->getPath().$filename, $this->getStream());
	}

	/**
	 * Ordner auslesen
	 * @return FolderAbstract[]
	 */
	public function getFolders() {
		throw new NotImplementedException();
	}

	/**
	 * Unter-Ordner
	 * @param string $foldername
	 * @return FolderAbstract
	 */
	public function getFolder($foldername) {
		return IO::getFolder($this->getPath().$foldername, $this->getStream());
	}

	/**
	 * Dateien und Ordner auslesen
	 * @return FolderAbstract[]|FileAbstract[]
	 */
	public function getFilesAndFolders() {
		return array_merge($this->getFolders(), $this->getFiles());
	}

	/**
	 * Ist Ordner leer?
	 * @return boolean
	 */
	public function isEmpty() {
		return count($this->getFilesAndFolders()) ? false : true;
	}

	/**
	 * Ordner umbenennen
	 * @param string $newname
	 * @return $this
	 */
	public function rename($newname) {
		self::checkName($newname);

		$newfolder = $this->getParentFolder()->getFolder($newname);
		if ($newfolder->exists())
			throw new \Exception(sprintf('Ein Ordner mit dem Namen "%s" existiert bereits', $newfolder->getName()), 409);

		$this->renameFolder($newfolder->getName(), $newfolder->getPath());
		$this->setName($newfolder->getName());
		return $this;
	}

	/**
	 * Ordner leeren
	 * @return $this
	 */
	public function clear() {
		foreach ($this->getFiles() as $file)
			$file->delete();

		foreach ($this->getFolders() as $folder)
			$folder->deleteRecursive();

		return $this;
	}

	/**
	 * Ordner mit Inhalt löschen
	 * @return $this
	 */
	public function deleteRecursive() {
		$this->clear();
		return $this->delete();
	}

	/**
	 * Gibt den übergeordneten Ordner zurück
	 * @return \FolderAbstract
	 */
	public function getParentFolder() {
		$path = $this->getParentPath();

		if (!$path)
			throw new \Exception('Ordner hat keinen übergeordneten Ordner');

		return IO::getFolder($path, $this->getStream());
	}

	/**
	 * In Unterordner wechseln
	 * @param string $path
	 * @return $this
	 */
	public function cd($path) {
		$this->setPath($this->getPath().self::fixPath($path));
		return $this;
	}

	/**
	 * Kopiert den Inhalt dieses Ordners in einen anderen Ordner
	 * @param FolderAbstract $destination
	 * @param callable $fileCallback bei false als rückgabewert wird das file nicht kopiert
	 * @param callable $folderCallback bei false als rückgabewert wird der ordner nicht kopiert
	 */
	public function copy(FolderAbstract $destination, $fileCallback = null, $folderCallback = null) {
		foreach ($this->getFiles() as $file) {
			$destinationFile = $destination->getFile($file->getName());

			if ($fileCallback && !call_user_func($fileCallback, $file, $destinationFile))
				continue;

			$file->copy($destinationFile, true);

			// Speicherplatzgrössenproblem ihrgendwas.... suchen, finden, töten
		}

		foreach ($this->getFolders() as $folder) {
			// Ordner anlegen
			$destinationFolder = $destination->getFolder($folder->getName());

			if ($folderCallback && !call_user_func($folderCallback, $folder, $destinationFolder))
				continue;

			if (!$destinationFolder->exists())
				$destinationFolder->create();

			$folder->copy($destinationFolder, $fileCallback, $folderCallback);
		}
	}

	/**
	 * Instanz clonen
	 * @return FolderAbstract
	 */
	public function getClone() {
		return IO::getFolder($this->getPath(), $this->getStream());
	}

	protected function setName($name) {
		self::checkName($name);
		$this->setPath($this->getParentPath().$name);
	}

	protected function createFolder() {
		throw new NotImplementedException();
	}

	protected function renameFolder($newname, $newpath) {
		throw new NotImplementedException();
	}

	protected function deleteFolder() {
		throw new NotImplementedException();
	}

	/**
	 * Ordnername prüfen
	 * @param string $name
	 * @throws \Exception
	 */
	public static function checkName(?string $name) {
		if (strpos($name, '/') !== false || strpos($name, '\\') !== false)
			throw new \Exception(sprintf('Ungültiger Ordnername: "%s"', $name));
	}

	/**
	 * Ordnerpfad fixen
	 * @param string $path
	 * @param boolean $addSlash
	 * @return string
	 */
	public static function fixPath(?string $path, bool $addSlash = true): ?string {
		$path = str_replace('\\', '/', $path);
		$path = trim($path);
		$path = rtrim($path, '/ ');

		// TODO wenn ordner "/.../" heisst, wird dies fehlerhaft erkannt
		if (strpos($path, './') !== false)
			throw new \Exception(sprintf('Ungültiger Pfad: "%s"', $path));

		if ($path == '')
			$path = '/';

		if ($path == '.')
			$path = '';

		if ($addSlash && $path)
			$path .= '/';

		return preg_replace('/\/{2,}/', '/', $path);
	}

}
