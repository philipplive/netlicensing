<?php
namespace HfCore;

/**
 * Image
 */
class Image {
	/**
	 * Zuschneiden auf exakte Grösse
	 */
	const RESIZE_CROP = 1;

	/**
	 * Proportional verkleinern auf Maximalgrösse
	 */
	const RESIZE_MAX = 2;

	/**
	 * Verkleinern unproportional auf exakte Grösse
	 */
	const RESIZE_EXACT = 3;

	/**
	 * @var resource
	 */
	public $handle;

	/**
	 * z.B. IMAGETYPE_PNG
	 * @var int
	 */
	public $type = null;

	/**
	 * Bildkonstruktor
	 * @param mixed $source string = pfad zu einem bild (oder url), Image = anderes bild, null = neues bild
	 * @param int $width
	 * @param int $height
	 */
	public function __construct($source = null, int $width = 100, int $height = 100) {
		// Neues Bild
		if ($source == null && $width != null && $height != null) {
			$this->handle = $this->imagecreatetruecolor($width, $height);
			$this->fill(Color::create([0, 0, 0, 0]));
		}

		// Bild von URL
		else if (Validator::isUrl($source))
			$this->loadUrl($source);

		// Bild aus String
		else if (is_string($source) && strlen($source) > 300)
			$this->loadFromString($source);

		// Bild aus Datei
		else if (is_string($source))
			$this->loadImage($source);

		// Bilddatei
		else if ($source instanceof self)
			$this->handle = $source->getImage();
	}

	/**
	 * Lade ein Bild von einer externen Quelle
	 * @param string $path
	 * @return $this
	 */
	public function loadUrl(string $path): self {
		$data = file_get_contents($path);
		if ($data === false)
			throw new \Exception(sprintf('Fehler beim Laden des Bildes "%s"', $path));

		$this->handle = imagecreatefromstring($data);

		if (!$this->handle)
			throw new \Exception(sprintf('Bild "%s" konnte nicht konvertiert werden', $path));

		return $this;
	}

	/**
	 * Bild laden
	 * @param string $path
	 * @param string $ext
	 * @return $this
	 */
	public function loadImage(string $path, ?string $ext = null): self {
		if (!is_readable($path))
			throw new \Exception(sprintf('Datei "%s" kann nicht gelesen werden', $path));

		if (!$ext)
			$ext = pathinfo($path, PATHINFO_EXTENSION);

		$ext = strtolower($ext);

		switch ($ext) {
			case 'jpg':
			case 'jpeg':
				$this->loadJpegImage($path);
				break;
			case 'png':
				$this->loadPngImage($path);
				break;
			case 'bmp':
				$this->loadBmpImage($path);
				break;
			case 'gif':
				$this->loadGifImage($path);
				break;
			default:
				throw new \Exception(sprintf('Dateiendung "%s" unbekannt', $ext));
		}

		return $this;
	}

	public function loadJpegImage(string $path): self {
		$this->type = IMAGETYPE_JPEG;
		$this->handle = imagecreatefromjpeg($path);

		if (!$this->handle)
			throw new \Exception(sprintf('Bild "%s" konnte nicht konvertiert werden', $path));

		return $this;
	}

	public function loadPngImage(string $path): self {
		$this->type = IMAGETYPE_PNG;
		$this->handle = imagecreatefrompng($path);

		if (!$this->handle)
			throw new \Exception(sprintf('Bild "%s" konnte nicht konvertiert werden', $path));

		imagealphablending($this->handle, true);
		imagesavealpha($this->handle, true);

		return $this;
	}

	public function loadGifImage(string $path): self {
		$this->type = IMAGETYPE_GIF;
		$this->handle = imagecreatefromgif($path);

		if (!$this->handle)
			throw new \Exception(sprintf('Bild "%s" konnte nicht konvertiert werden', $path));

		return $this;
	}

	public function loadBmpImage(string $path): self {
		$this->type = IMAGETYPE_BMP;
		$this->handle = imagecreatefromwbmp($path);

		if (!$this->handle)
			throw new \Exception(sprintf('Bild "%s" konnte nicht konvertiert werden', $path));

		return $this;
	}

	/**
	 * Erzeugt ein ImageHandle aus einem string
	 * @param string $string
	 * @return $this
	 */
	public function loadFromString(string $string): self {
		$this->handle = imagecreatefromstring($string);

		if (!$this->handle)
			throw new \Exception('Bild aus String konnte nicht konvertiert werden');

		imagealphablending($this->handle, true);
		imagesavealpha($this->handle, true);
		return $this;
	}

	/**
	 * Bild verkleinern, beschneiden, verzerren
	 * @param int $width Max-Breite
	 * @param int $height Max-Höhe
	 * @param int $type RESIZE_MAX, RESIZE_CROP, RESIZE_MAX
	 * @return $this
	 */
	public function resize(int $width = 0, int $height = 0, int $type = self::RESIZE_MAX): self {
		$oWidth = $this->getX();
		$oHeight = $this->getY();
		$factor = $oWidth / $oHeight;
		if (!$width && !$height)
			throw new \Exception('Keine Grösse angegeben');

		if (!$height)
			$height = round($width / $factor);
		else if (!$width)
			$width = round($height * $factor);

		switch ($type) {
			case self::RESIZE_CROP:
				$widthNew = $width;
				$heightNew = round($width / $factor);
				$offsetX = 0;
				$offsetY = round(($heightNew - $height) / 5);
				if ($heightNew < $height) {
					$heightNew = $height;
					$widthNew = round($height * $factor);
					$offsetX = round(($widthNew - $width) / 2);
					$offsetY = 0;
				}
				$handle = $this->imagecreatetruecolor($width, $height);
				imagecopyresampled($handle, $this->handle, -$offsetX, -$offsetY, 0, 0, $widthNew, $heightNew, $oWidth, $oHeight);
				break;

			case self::RESIZE_MAX:
				if ($oWidth <= $width && $oHeight <= $height)
					return $this;

				$widthNew = $width;
				$heightNew = round($width / $factor);
				if ($heightNew > $height) {
					$heightNew = $height;
					$widthNew = round($height * $factor);
				}
				$handle = $this->imagecreatetruecolor($widthNew, $heightNew);
				imagecopyresampled($handle, $this->handle, 0, 0, 0, 0, $widthNew, $heightNew, $oWidth, $oHeight);
				break;

			case self::RESIZE_EXACT:
				$handle = $this->imagecreatetruecolor($width, $height);
				imagecopyresampled($handle, $this->handle, 0, 0, 0, 0, $width, $height, $oWidth, $oHeight);
				break;
			default:
				throw new \Exception('Unbekannter Resize-Typ');
		}

		$this->handle = $handle;
		return $this;
	}

	/**
	 * Bild ausschneiden
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return $this
	 */
	public function crop(int $x, int $y, int $width, int $height): self {
		$tempHandle = $this->imagecreatetruecolor($width, $height);
		imagecopy($tempHandle, $this->handle, 0, 0, $x, $y, $width, $height);
		$this->handle = $tempHandle;
		return $this;
	}

	/**
	 * Bild drehen
	 * Achtung! problematisch wenn text hineingeschrieben wird
	 * @param int $angle
	 * @return $this
	 */
	public function rotate(int $angle): self {
		$this->handle = imagerotate($this->handle, 360 - $angle, 0);
		return $this;
	}

	/**
	 * Breite des Bildes
	 * @return int
	 */
	public function getX(): int {
		return imagesx($this->handle);
	}

	/**
	 * Breite des Bildes
	 * @return int
	 */
	public function getWidth(): int {
		return $this->getX();
	}

	/**
	 * Höhe des Bildes
	 * @return int
	 */
	public function getY(): int {
		return imagesy($this->handle);
	}

	/**
	 * Höhe des Bildes
	 * @return int
	 */
	public function getHeight(): int {
		return $this->getY();
	}

	/**
	 *
	 * @param string $text
	 * @param int $x
	 * @param int $y
	 * @param Color $color
	 * @param int $size
	 * @param int $angle
	 * @param string|null $font z.B. 'resources/fonts/myriadpro.ttf'
	 * @return $this
	 */
	public function drawString(string $text, int $x, int $y, Color $color, int $size = 10, int $angle = 0, ?string $font = null): self {
		if (!$font)
			$font = realpath('resources/fonts/myriadpro.ttf');

		imagettftext($this->handle, $size, $angle, $x, $y, $this->newColor($color), $font, $text);
		return $this;
	}

	/**
	 *
	 * @param string $text
	 * @param int $size
	 * @param int $angle
	 * @param string $font
	 * @return array|bool
	 */
	public function getStringDimensionBox(string $text, int $size = 10, int $angle = 0, ?string $font = null) {
		if (!$font)
			$font = realpath('resources/fonts/myriadpro.ttf');

		return imagettfbbox($size, $angle, $font, $text);
	}

	/**
	 * Farbe
	 * @param Color Farbe
	 * @return int
	 */
	public function newColor(Color $color): int {
		if ($color->a == 1)
			return imagecolorallocate($this->handle, $color->r, $color->g, $color->b);

		return imagecolorallocatealpha($this->handle, $color->r, $color->g, $color->b, round((1 - $color->a) * 127));
	}

	/**
	 * Zeichnet eine linie zwischen zwei punkten
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 * @param Color $color
	 * @param int $thickness
	 * @return $this
	 */
	public function drawLine(int $x1, int $y1, int $x2, int $y2, Color $color, int $thickness = 1) {
		imagesetthickness($this->handle, $thickness);
		imageline($this->handle, $x1, $y1, $x2, $y2, $this->newColor($color));
		return $this;
	}

	/**
	 * Zeichnet ein rechteck
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 * @param Color $color
	 * @param bool $filled
	 * @param int $thickness
	 * @return $this
	 */
	public function drawRectangle(int $x1, int $y1, int $x2, int $y2, Color $color, bool $filled = false, int $thickness = 1) {
		imagesetthickness($this->handle, $thickness);
		if ($filled)
			imagefilledrectangle($this->handle, $x1, $y1, $x2, $y2, $this->newColor($color));
		else
			imagerectangle($this->handle, $x1, $y1, $x2, $y2, $this->newColor($color));

		return $this;
	}

	/**
	 * Färbt das entsprechende Bild ein
	 * @param Color $color
	 * @return $this
	 */
	public function colorize(Color $color) {
		imagefilter($this->handle, IMG_FILTER_COLORIZE, $color->r, $color->g, $color->b);
		return $this;
	}

	/**
	 * Ändert die Farben des Bildes
	 * @param Color $color
	 * @return $this
	 */
	public function replaceColor(Color $color) {
		$image = $this->imagecreatetruecolor($this->getX(), $this->getY());
		for ($x = 0; $x < $this->getX(); $x++) {
			for ($y = 0; $y < $this->getY(); $y++) {
				$col = imagecolorsforindex($this->handle, imagecolorat($this->handle, $x, $y));
				imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $color->r, $color->g, $color->b, $col['alpha']));
			}
		}
		$this->handle = $image;
		return $this;
	}

	/**
	 * Farben entfernen
	 * @return $this
	 */
	public function grayscale() {
		imagefilter($this->handle, IMG_FILTER_GRAYSCALE);
		return $this;
	}

	/**
	 * Füllt die fläche farbig
	 * @param Color $color
	 * @return $this
	 */
	public function fill(Color $color) {
		imagefill($this->handle, 0, 0, $this->newColor($color));
		return $this;
	}

	/**
	 * Hintergrundfarbe setzen (bei PNG)
	 * @param Color $color
	 * @return $this
	 */
	public function setBackgroundColor(Color $color): self {
		$newHandle = imagecreatetruecolor($this->getWidth(), $this->getHeight());
		imagefill($newHandle, 0, 0, $this->newColor($color));

		imagecopyresampled(
			$newHandle, $this->handle,
			0, 0, 0, 0,
			$this->getWidth(), $this->getHeight(),
			$this->getWidth(), $this->getHeight()
		);

		$this->handle = $newHandle;
		return $this;
	}

	/**
	 * Kopiere Bild in Bild
	 * @param Image $img bild, welches hineinkopiert wird
	 * @param int $x x abstand
	 * @param int $y y abstand
	 * @return $this
	 */
	public function merge(Image $img, int $x, int $y) {
		imagecopy($this->handle, $img->getHandle(), $x, $y, 0, 0, $img->getX(), $img->getY());
		return $this;
	}

	/**
	 * Farben extrahieren
	 * @param int $limit
	 * @param Color $background
	 * @return Color[]
	 */
	public function extractColors(int $limit = 10, Color $background = null): array {
		$areColorsIndexed = !imageistruecolor($this->handle);
		$imageWidth = $this->getWidth();
		$imageHeight = $this->getHeight();
		$colors = [];
		for ($x = 0; $x < $imageWidth; ++$x) {
			for ($y = 0; $y < $imageHeight; ++$y) {
				$color = imagecolorat($this->handle, $x, $y);
				if ($areColorsIndexed) {
					$colorComponents = imagecolorsforindex($this->handle, $color);
					$color = ($colorComponents['alpha'] * 16777216) +
						($colorComponents['red'] * 65536) +
						($colorComponents['green'] * 256) +
						($colorComponents['blue']);
				}
				if ($alpha = $color >> 24) {
					if (!$background)
						continue;

					$alpha /= 127;
					$color = (int)(($color >> 16 & 0xFF) * (1 - $alpha) + $background->r * $alpha) * 65536 +
						(int)(($color >> 8 & 0xFF) * (1 - $alpha) + $background->g * $alpha) * 256 +
						(int)(($color & 0xFF) * (1 - $alpha) + $background->b * $alpha);
				}
				if (isset($colors[$color]))
					$colors[$color] += 1;
				else
					$colors[$color] = 1;
			}
		}
		arsort($colors);
		$list = [];
		foreach ($colors as $rgb => $count) {
			$item = new Color();
			$item->count = $count;
			$item->r = $rgb >> 16;
			$item->g += $rgb >> 8 & 255;
			$item->b += $rgb & 255;
			$list[] = $item;
			if (count($list) >= $limit)
				return $list;
		}

		return $list;
	}

	/**
	 * Hauptfarbe auslesen
	 * @param boolean $gray
	 * @return Color
	 */
	public function getPrimaryColor(bool $gray = false): Color {
		$colors = $this->extractColors();
		foreach ($colors as $color)
			$color->diff = abs($color->r - $color->g) + abs($color->g - $color->b);

		foreach ($colors as $color) {
			if (!$gray && $color->diff < 20)
				continue;

			return $color;
		}
		return array_shift($colors);
	}

	/**
	 * Gibt eine kopie (clone) des aktuellen handles zurück
	 * @return self
	 */
	public function getImage() {
		return clone $this->handle;
	}

	/**
	 * gibt den aktuellen handle zurück
	 * @return resource
	 */
	public function getHandle() {
		return $this->handle;
	}

	/**
	 * Bild ausgeben
	 * @param int $type
	 * @param int $quality
	 * @return $this
	 */
	public function render(int $type = IMAGETYPE_PNG, int $quality = null) {
		switch ($type) {
			case IMAGETYPE_PNG:
				imagepng($this->handle, null, $quality === null ? 6 : $quality);
				break;
			case IMAGETYPE_JPEG:
				imagejpeg($this->handle, null, $quality === null ? 80 : $quality);
				break;
			case IMAGETYPE_GIF:
				imagegif($this->handle);
				break;
			default:
				throw new \Exception('Imagetype nicht gefunden: '.$type);
		}

		return $this;
	}

	/**
	 * Bild speichern
	 * @param string $path
	 * @param int $type z.B. IMAGETYPE_PNG oder IMAGETYPE_JPEG
	 * @param int $quality
	 * @return $this
	 */
	public function save(string $path, int $type = IMAGETYPE_UNKNOWN, ?int $quality = null) {
		if (!$type) {
			switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
				case 'jpg':
				case 'jpeg':
					$type = IMAGETYPE_JPEG;
					break;
				case 'png':
					$type = IMAGETYPE_PNG;
					break;
				case 'bmp':
					$type = IMAGETYPE_BMP;
					break;
				case 'gif':
					$type = IMAGETYPE_GIF;
					break;
				default:
					$type = IMAGETYPE_PNG;
					new Warning(sprintf('Konnte ImageType nicht erkennen: "%s"', $path));
			}
		}

		switch ($type) {
			case IMAGETYPE_PNG:
				imagepng($this->handle, $path, $quality === null ? 6 : $quality);
				break;
			case IMAGETYPE_JPEG:
				imagejpeg($this->handle, $path, $quality === null ? 80 : $quality);
				break;
			case IMAGETYPE_GIF:
				imagegif($this->handle, $path);
				break;
			default :
				throw new \Exception('Kann ImageType nicht speichern: '.$type);
		}

		return $this;
	}

	/**
	 * Gibt das aktuelle Bild als String zurück (stream)
	 * @param int $type
	 * @param int|null $quality
	 * @return string
	 */
	public function getString(int $type = IMAGETYPE_PNG, int $quality = null): string {
		ob_start();
		try {
			$this->render($type, $quality);
			$content = ob_get_contents();
		} catch (\Exception $ex) {
			ob_end_clean();
			throw $ex;
		}
		ob_end_clean();
		return $content;
	}

	/**
	 * Als Data-URL
	 * @param int|null $quality
	 * @return string
	 */
	public function getDataURL(int $quality = null): string {
		return 'data:image/png;base64,'.base64_encode($this->getString(IMAGETYPE_PNG, $quality));
	}

	/**
	 * Bild als HTML-Node (inline)
	 * @return HtmlNode
	 */
	public function getHtml(): HtmlNode {
		return HtmlNode::img()->attr('width', $this->getX())->attr('height', $this->getY())->attr('src', $this->getDataURL());
	}

	public function __destruct() {
		if ($this->handle)
			imagedestroy($this->handle);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return resource
	 */
	protected function imagecreatetruecolor(int $width, int $height) {
		$handle = imagecreatetruecolor($width, $height);
		imagealphablending($handle, false);
		imagesavealpha($handle, true);
		return $handle;
	}

	/**
	 * Pfad öffnen
	 * @param string $path
	 * @return self
	 */
	public static function open(string $path) {
		return new self($path);
	}

	/**
	 * Neues Bild
	 * @param int $width
	 * @param int $height
	 * @return self
	 */
	public static function create(int $width, int $height) {
		return new self(null, $width, $height);
	}
}
