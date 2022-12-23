<?php
namespace HfCore;

/**
 * Price
 */
class Price {
	/**
	 * Währung
	 * @var string
	 */
	public static $currency = 'chf';

	/**
	 * Preis formatieren
	 * @param double|null $value
	 * @param boolean $short Währung nicht anhängen
	 * @param boolean $thousands ' Zeichen bei 1000
	 * @return string
	 */
	public static function format(?float $value, bool $short = false, bool $thousands = true): string {
		if ($value === null)
			$value = 0.0;

		return number_format($value, 2, '.', $thousands ? '\'' : '').($short ? '' : ' '.self::getCurrency());
	}

	/**
	 * Gibt aktuelle Währung aus
	 * @return string
	 */
	public static function getCurrency(): string {
		return strtoupper(self::$currency);
	}

	/**
	 * Betrag Währungsgerecht runden
	 * @param double $value
	 * @return double
	 */
	public static function round(float $value): float {
		return round($value * 20) / 20;
	}

}
