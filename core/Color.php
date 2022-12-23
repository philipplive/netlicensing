<?php
namespace HfCore;

/**
 * Color
 */
class Color {
	/**
	 * @var int Rot-Kanal
	 */
	public  $r = 0;

	/**
	 * @var int Grün-Kanal
	 */
	public  $g = 0;

	/**
	 * @var int Blau-Kanal
	 */
	public  $b = 0;

	/**
	 * @var float Alpha-Kanal
	 */
	public $a = 1;

	/**
	 * Neue Farbe erstellen
	 * @param mixed $color
	 */
	public function __construct($color = null) {
		if ($color)
			$this->fromMixed($color);
	}

	/**
	 * Neue Farbe erstellen
	 * @param mixed $color
	 * @return Color
	 */
	public static function create($color = null) {
		return new self($color);
	}

	/**
	 * Kopie von Color erstellen
	 * @return Color
	 */
	public function getClone(): Color {
		return clone $this;
	}

	/**
	 * Helligkeit anpassen
	 * @param int $value
	 * @return $this
	 */
	public function changeBrightness(int $value): self {
		$this->r = max(0, min($this->r + $value, 255));
		$this->g = max(0, min($this->g + $value, 255));
		$this->b = max(0, min($this->b + $value, 255));
		return $this;
	}

	/**
	 * Alphakanal setzen
	 * @param float $a
	 * @return $this
	 */
	public function setAlpha(float $a): self {
		$this->a = $a;
		return $this;
	}

	/**
	 * Aus verschiedenem
	 * @param mixed $color
	 * @return $this
	 */
	public function fromMixed($color): self {
		// Color
		if ($color instanceof Color)
			return $this->fromRgb($color->r, $color->g, $color->b, $color->a);

		// array(0,0,0)
		if (is_array($color))
			return $this->fromRgb($color[0], $color[1], $color[2], isset($color[3]) ? $color[3] : 1);

		// #fff
		if (preg_match('/\#[a-f0-9]{3}$/i', $color))
			return $this->fromHex4($color);

		// #ffffff
		if (preg_match('/\#[a-f0-9]{6}$/i', $color))
			return $this->fromHex($color);

		// rgb(155,155,155)
		if (preg_match('/rgb\( ?(\d+) ?, ?(\d+) ?, ?(\d+) ?\)/i', $color, $data))
			return $this->fromRgb($data[1], $data[2], $data[3]);

		// rgba(155,155,155,0)
		if (preg_match('/rgba\( ?(\d+) ?, ?(\d+) ?, ?(\d+) ?, ?(.+)\)/i', $color, $data))
			return $this->fromRgb($data[1], $data[2], $data[3], $data[4]);

		new Warning(sprintf('Konvertierung von "%s" gescheitert', $color));

		return $this;
	}

	/**
	 * Aus Hex Wert
	 * @param string $hex (#ffffff)
	 * @return $this
	 */
	public function fromHex(string $hex): self {
		$rgb = [];
		for ($i = 1; $i < strlen($hex); $i += 2)
			array_push($rgb, hexdec(substr($hex, $i, 2)));

		$this->r = $rgb[0];
		$this->g = $rgb[1];
		$this->b = $rgb[2];

		return $this;
	}

	/**
	 * Aus 4bit Hex
	 * @param string $hex (#fff)
	 * @return $this
	 */
	public function fromHex4(string $hex): self {
		$new = '#';
		for ($i = 1; $i < 4; $i++)
			$new .= $hex[$i].$hex[$i];

		return $this->fromHex($new);
	}

	/**
	 * Aus RGB oder RGBA oder Array
	 * @param mixed $rgb Array oder Rot-Kanal
	 * @param int $g Grün-Kanal
	 * @param int $b Blau-Kanal
	 * @param float $a Alpha-Kanal
	 * @return $this
	 */
	public function fromRgb($rgb, int $g = 0, int $b = 0, float $a = 1): self {
		if (is_array($rgb)) {
			$this->r = $rgb[0];
			$this->g = $rgb[1];
			$this->b = $rgb[2];
			$this->a = isset($rgb[3]) ? $rgb[3] : 1;
			if ($this->a > 1)
				$this->a = round($this->a / 100, 3);
		}
		else {
			$this->r = $rgb;
			$this->g = $g;
			$this->b = $b;
			$this->a = $a;
		}
		return $this;
	}

	/**
	 * zu Hex
	 * @return string (#ffffff)
	 */
	public function toHex(): string {
		$rgb = array($this->r, $this->g, $this->b);

		foreach ($rgb as &$b) {
			$b = dechex($b);
			if (strlen($b) == 1)
				$b = '0'.$b;
		}

		return '#'.implode('', $rgb);
	}

	/**
	 * Erzeugt RGB String
	 * @return string rgb(r,g,b)
	 */
	public function toRgbStr(): string {
		return sprintf('rgb(%s,%s,%s)', $this->r, $this->g, $this->b);
	}

	/**
	 * Erzeugt RGBA String
	 * @return string rgba(r,g,b,a)
	 */
	public function toRgbaStr(): string {
		return sprintf('rgba(%s,%s,%s,%s)', $this->r, $this->g, $this->b, number_format($this->a, 2, '.', ''));
	}

	/**
	 * Erzeugt Array
	 * @return array
	 */
	public function toArray(): array {
		return [$this->r, $this->g, $this->b, $this->a];
	}

	/**
	 * Zufallsfarbe
	 * @return self
	 */
	public static function rand(): self {
		return self::create()->fromRgb(rand(0, 255), rand(0, 255), rand(0, 255));
	}

	/**
	 * Berechnet den Durchschnitt zweier Farben
	 * @param self $color1 Farbe 1
	 * @param self $color2 Farbe 2
	 * @return self
	 */
	public static function getAverage(self $color1, self $color2): self {
		$alpha = $color1->a;
		$color1 = [$color1->r, $color1->g, $color1->b];
		$color2 = [$color2->r, $color2->g, $color2->b];

		$color = [0, 0, 0];
		foreach ($color1 as $i => $col)
			$color[$i] = round(($color1[$i] + $color2[$i]) / 2);

		$new = new self($color);
		$new->a = $alpha;

		return $new;
	}

}
