<?php

namespace HfCore;

abstract class FileAbstract extends FileInfo {
	/**
	 * Content-Cache
	 * @var string
	 */
	protected $content = null;

	/**
	 * Dateinamen mit oder ohne Dateiendung
	 * @param boolean $extension
	 * @return string
	 */
	public function getName(bool $extension = true): string {
		if ($extension)
			return pathinfo($this->getPath(), PATHINFO_BASENAME);
		else
			return pathinfo($this->getPath(), PATHINFO_FILENAME);
	}

	/**
	 * Dateiendung
	 * @return string|null
	 */
	public function getExtension(): ?string {
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}

	/**
	 * Ordnerpfad der Datei
	 * @return string|null
	 */
	public function getFolderPath(): ?string {
		return FolderAbstract::fixPath(pathinfo($this->getPath(), PATHINFO_DIRNAME));
	}

	/**
	 * Gibt den aktuellen Ordner des Files zurück
	 * @return FolderAbstract
	 */
	public function getFolder(): FolderAbstract {
		return IO::getFolder($this->getFolderPath(), $this->getStream());
	}

	/**
	 * Leere Datei erstellen
	 * @return $this
	 */
	public function create(): self {
		$this->createFile();
		$this->content = null;
		$this->lastChange = null;
		$this->size = null;
		return $this;
	}

	/**
	 * Datei umbenennen
	 * @param string $newname
	 * @return $this
	 */
	public function rename(string $newname): self {
		self::checkName($newname);
		$newfile = $this->getFolder()->getFile($newname);
		if ($newfile->exists())
			throw new Exception(sprintf('Eine Datei mit dem Namen "%s" existiert bereits', $newfile->getName()), 409);

		$this->renameFile($newfile->getName(), $newfile->getPath());
		$this->setName($newfile->getName());
		return $this;
	}

	/**
	 * Datei löschen
	 * @return $this
	 */
	public function delete(): self {
		$this->deleteFile();
		return $this;
	}

	/**
	 * Änderungsdatum aktualisieren
	 * @param DateTime $time
	 * @return $this
	 */
	public function touch(?\DateTime $time = null): self {
		$this->touchFile($time);
		return $this;
	}

	/**
	 * Kopiert den Inhalt dieses Files in ein anderes
	 * @param FileAbstract|FolderAbstract $target
	 * @param boolean $replace
	 * @return $this
	 */
	public function copy($target, bool $replace = false): self {
		if ($target instanceof FolderAbstract)
			$target = $target->getFile($this->getName());

		if ($target instanceof FileAbstract)
			$this->copyFile($target, $replace);
		else
			throw new Exception('Unbekanntes Ziel');

		return $this;
	}

	/**
	 * Inhalt der Datei setzen
	 * @param string|null $content
	 * @return $this
	 */
	public function write(?string $content): self {
		$this->writeContent($content);
		$this->content = null;
		$this->lastChange = null;
		$this->size = null;
		return $this;
	}

	/**
	 * Text hinzufügen
	 * @param string|null $value
	 * @return $this
	 */
	public function append(?string $value): self {
		$content = $this->read();
		$content .= $value;
		$this->write($content);
		return $this;
	}

	/**
	 * Fügt eine neue Textzeile hinzu
	 * @param string|null $value
	 * @return $this
	 */
	public function appendLine(?string $value): self {
		return $this->append($value.PHP_EOL);
	}

	/**
	 * Setzt den Inhalt zurück
	 * @return $this
	 */
	public function clear(): self {
		return $this->write('');
	}

	/**
	 * Inhalt der Datei lesen
	 * @param boolean $forced
	 * @return string
	 */
	public function read(bool $forced = false): ?string {
		if (!$forced && $this->content !== null)
			return $this->content;

		return $this->content = $this->readContent();
	}

	/**
	 * Linien als Array
	 * @return string[]
	 */
	public function readLines(): array {
		return preg_split('/((\r(?!\n))|((?<!\r)\n)|(\r\n))/', $this->read());
	}

	/**
	 * Ist Datei leer?
	 * @return boolean
	 */
	public function isEmpty(): bool {
		return $this->read() ? true : false;
	}

	/**
	 * MimeType abfragen
	 * @return string|null
	 */
	public function getMimeType(): ?string {
		return self::getMimeTypeByExtension($this->getExtension());
	}

	protected function setName($name, $appendExtension = false) {
		if ($appendExtension)
			$name .= '.'.$this->getExtension();

		self::checkName($name);
		$this->setPath($this->getFolderPath().$name);
	}

	protected function createFile() {
		$this->writeContent('');
	}

	protected function writeContent($content) {
		throw new NotImplementedException();
	}

	protected function readContent() {
		throw new NotImplementedException();
	}

	protected function renameFile($newname, $newpath) {
		throw new NotImplementedException();
	}

	protected function deleteFile() {
		throw new NotImplementedException();
	}

	protected function touchFile(?\DateTime $time = null) {
		throw new NotImplementedException();
	}

	protected function copyFile(FileAbstract $target, $replace = false) {
		if (!$replace && $target->exists())
			throw new Exception(sprintf('Datei "%s" exitiert bereits', $target->getPath()));

		$target->write($this->read());
	}

	/**
	 * Mime-Type nach Extension
	 * @param string $ext
	 * @return string
	 */
	public static function getMimeTypeByExtension(string $ext): string {
		$ct = [];
		$ct['htm'] = 'text/html';
		$ct['html'] = 'text/html';
		$ct['php'] = 'text/plain';
		$ct['txt'] = 'text/plain';
		$ct['asc'] = 'text/plain';
		$ct['bmp'] = 'image/bmp';
		$ct['gif'] = 'image/gif';
		$ct['jpeg'] = 'image/jpeg';
		$ct['jpg'] = 'image/jpeg';
		$ct['jpe'] = 'image/jpeg';
		$ct['png'] = 'image/png';
		$ct['ico'] = 'image/vnd.microsoft.icon';
		$ct['mpeg'] = 'video/mpeg';
		$ct['mpg'] = 'video/mpeg';
		$ct['mpe'] = 'video/mpeg';
		$ct['qt'] = 'video/quicktime';
		$ct['mov'] = 'video/quicktime';
		$ct['avi'] = 'video/x-msvideo';
		$ct['wmv'] = 'video/x-ms-wmv';
		$ct['mp2'] = 'audio/mpeg';
		$ct['mp3'] = 'audio/mpeg';
		$ct['rm'] = 'audio/x-pn-realaudio';
		$ct['ram'] = 'audio/x-pn-realaudio';
		$ct['rpm'] = 'audio/x-pn-realaudio-plugin';
		$ct['ra'] = 'audio/x-realaudio';
		$ct['wav'] = 'audio/x-wav';
		$ct['css'] = 'text/css';
		$ct['zip'] = 'application/zip';
		$ct['rar'] = 'application/x-rar-compressed';
		$ct['pdf'] = 'application/pdf';
		$ct['doc'] = 'application/msword';
		$ct['vcf'] = 'text/x-vCard';
		$ct['bin'] = 'application/octet-stream';
		$ct['exe'] = 'application/octet-stream';
		$ct['class'] = 'application/octet-stream';
		$ct['dll'] = 'application/octet-stream';
		$ct['xls'] = 'application/vnd.ms-excel';
		$ct['ppt'] = 'application/vnd.ms-powerpoint';
		$ct['wbxml'] = 'application/vnd.wap.wbxml';
		$ct['wmlc'] = 'application/vnd.wap.wmlc';
		$ct['wmlsc'] = 'application/vnd.wap.wmlscriptc';
		$ct['dvi'] = 'application/x-dvi';
		$ct['spl'] = 'application/x-futuresplash';
		$ct['gtar'] = 'application/x-gtar';
		$ct['gzip'] = 'application/x-gzip';
		$ct['js'] = 'application/x-javascript';
		$ct['swf'] = 'application/x-shockwave-flash';
		$ct['tar'] = 'application/x-tar';
		$ct['xhtml'] = 'application/xhtml+xml';
		$ct['au'] = 'audio/basic';
		$ct['snd'] = 'audio/basic';
		$ct['midi'] = 'audio/midi';
		$ct['mid'] = 'audio/midi';
		$ct['m3u'] = 'audio/x-mpegurl';
		$ct['tiff'] = 'image/tiff';
		$ct['tif'] = 'image/tiff';
		$ct['rtf'] = 'text/rtf';
		$ct['wml'] = 'text/vnd.wap.wml';
		$ct['wmls'] = 'text/vnd.wap.wmlscript';
		$ct['xsl'] = 'text/xml';
		$ct['xml'] = 'text/xml';

		if (isset($ct[$ext]))
			return $ct[$ext];

		return 'application/octet-stream';
	}

	public static function checkName(?string $name) {
		if (strpos($name, '/') !== false || strpos($name, '\\') !== false)
			throw new Exception(sprintf('Ungültiger Dateiname: "%s"', $name));
	}

	public static function fixPath(?string $path): ?string {
		return FolderAbstract::fixPath($path, false);
	}

}
