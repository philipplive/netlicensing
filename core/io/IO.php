<?php
namespace HfCore;

abstract class IO {
	/**
	 * File-Instanz
	 * @param string $path
	 * @param null|\FTP|\SSH|\SFTP|\Powershell $stream
	 * @return \FileLocal|\FileFTP|\FileSSH|\FileSFTP|\FilePowershell
	 * @throws Exception
	 */
	public static function getFile($path, $stream = null): FileAbstract {
		if ($path instanceof FileAbstract)
			return self::getFile($path->getPath(), $path->getStream());

		if ($stream == null)
			return new FileLocal($path);
		else if ($stream instanceof \FTP)
			return new FileFTP($path, $stream);
		else if ($stream instanceof \SFTP)
			return new FileSFTP($path, $stream);
		else if ($stream instanceof \SSH)
			return new FileSSH($path, $stream);
		else if ($stream instanceof \Powershell)
			return new FilePowershell($path, $stream);

		throw new Exception('Filetreiber nicht gefunden ('.get_class($stream).')');
	}

	/**
	 * Flüchtige Datei erstellen
	 * @return FileVolatile
	 */
	public static function getVolatileFile(): FileVolatile {
		return new FileVolatile();
	}

	/**
	 * Erstelle flüchtige Datei aus DataURL
	 * @param string $dataurl
	 * @return FileVolatile
	 */
	public static function fromDataURL(string $dataurl): FileVolatile {
		return self::getVolatileFile()->writeFromDataURL($dataurl);
	}

	/**
	 * Gibt den Pfad einer Temporären Datei zurück
	 * @param string $prefix
	 * @param string $extension
	 * @return \FileAbstract
	 */
	public static function getTempFile(?string $prefix = null, ?string $extension = ''): FileAbstract {
		if ($extension)
			$extension = '.'.$extension;

		return self::getFile(self::getTempPath($prefix, $extension));
	}

	/**
	 * Pfad zu einem Tempfile
	 * @param string $prefix Datei-Prefix
	 * @param string $extension Dateiendung
	 * @return string
	 */
	public static function getTempPath(?string $prefix = null, ?string $extension = ''): string {
		return FileAbstract::fixPath(tempnam(sys_get_temp_dir(), $prefix).$extension);
	}

	/**
	 * File lesen
	 * @param string $path
	 * @param mixed $stream
	 * @return string
	 */
	public static function readFile($path, $stream = null): ?string {
		return self::getFile($path, $stream)->read();
	}

	/**
	 * Datei schreiben
	 * @param string $path
	 * @param mixed $stream
	 * @param string $content
	 * @return FileAbstract
	 */
	public static function writeFile($path, $stream = null, string $content = ''): FileAbstract {
		return self::getFile($path, $stream)->write($content);
	}

	/**
	 * Folder-Instanz
	 * @param string $path
	 * @param null|\FTP|\SSH|\SFTP|\Powershell $stream
	 * @return \FolderLocal|\FolderFTP|\FolderSSH|\FolderSFTP|\FolderPowershell
	 * @throws Exception
	 */
	public static function getFolder($path, $stream = null): FolderAbstract {
		if ($path instanceof FolderAbstract)
			return self::getFolder($path->getPath(), $path->getStream());

		if ($stream == null)
			return new FolderLocal($path);
		else if ($stream instanceof \FTP)
			return new FolderFTP($path, $stream);
		else if ($stream instanceof \SFTP)
			return new FolderSFTP($path, $stream);
		else if ($stream instanceof \SSH)
			return new FolderSSH($path, $stream);
		else if ($stream instanceof \Powershell)
			return new FolderPowershell($path, $stream);

		throw new Exception('Filetreiber nicht gefunden ('.get_class($stream).')');
	}

	/**
	 * ID Property aus Pfad oder Name generieren (z.B. für ListView)
	 * @param FolderAbstract[]|FileAbstract[] $items
	 * @param bool $fullPath
	 * @return FolderAbstract[]|FileAbstract[]
	 */
	public static function mapPathToIdProperty(array $items, bool $fullPath = false): array {
		foreach ($items as $item)
			$item->id = $fullPath ? $item->getPath() : $item->getName();

		return $items;
	}

}
