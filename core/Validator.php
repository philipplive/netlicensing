<?php
namespace HfCore;

/**
 * Validierung etc.
 */
class Validator {
	const T_STR = 1;
	const T_INT = 10;
	const T_NUMERIC = 11;
	const T_EMAIL = 20;
	const T_URL = 21;
	const T_IP = 22;
	const T_PASSWORD = 23;
	const T_DOMAIN = 24;
	const T_DATE = 30;
	const T_DATETIME = 31;
	const T_TIME = 32;
	const T_PHONE = 33;
	const E_UNKNOWN = 0;
	const E_REQUIRED = 1;
	const E_MIN = 2;
	const E_MAX = 3;
	const E_NUM_MIN = 4;
	const E_NUM_MAX = 5;
	const E_INT = 10;
	const E_NUMERIC = 11;
	const E_EMAIL = 20;
	const E_URL = 21;
	const E_IP = 22;
	const E_PASSWORD = 23;
	const E_DOMAIN = 24;
	const E_DATE = 30;
	const E_DATETIME = 31;
	const E_TIME = 32;
	const E_PHONE = 33;

	public $min = null;
	public $max = null;
	public $type = 1;
	public $required = false;

	/**
	 * @var int Fehlercode gibt an an was die Validierung gescheitert ist
	 */
	public $errorCode = 0;

	/**
	 * Prüft ob der String der Validierung entspricht
	 * @param string $str
	 * @return bool
	 */
	public function validate($str) {
		$this->errorCode = 0;

		if (empty($str)) {
			if ($this->required){
				$this->errorCode = self::E_REQUIRED;
				return false;
			}
			return true;
		}

		switch ($this->type) {
			case self::T_INT:
				if (!self::isInt($str)) {
					$this->errorCode = self::E_INT;
					return false;
				}
				break;
			case self::T_NUMERIC:
				if (!self::isNumeric($str)) {
					$this->errorCode = self::E_NUMERIC;
					return false;
				}
				break;
			case self::T_EMAIL:
				if (!self::isEmail($str)) {
					$this->errorCode = self::E_EMAIL;
					return false;
				}
				break;
			case self::T_URL:
				if (!self::isUrl($str)) {
					$this->errorCode = self::E_URL;
					return false;
				}
				break;
			case self::T_IP:
				if (!self::isIp($str)) {
					$this->errorCode = self::E_IP;
					return false;
				}
				break;
			case self::T_PASSWORD:
				if (!self::isPassword($str)) {
					$this->errorCode = self::E_PASSWORD;
					return false;
				}
				break;
			case self::T_DOMAIN:
				if (!self::isDomain($str)) {
					$this->errorCode = self::T_DOMAIN;
					return false;
				}
				break;
			case self::T_PHONE:
				if (!self::isPhone($str)) {
					$this->errorCode = self::E_PHONE;
					return false;
				}
				break;
			case self::T_DATE:
				if (!self::isDate($str)) {
					$this->errorCode = self::E_DATE;
					return false;
				}
				break;
			case self::T_TIME:
				if (!self::isTime($str)) {
					$this->errorCode = self::E_TIME;
					return false;
				}
				break;
			case self::T_DATETIME:
				if (!self::isDatetime($str)) {
					$this->errorCode = self::E_DATETIME;
					return false;
				}
				break;
		}

		if ($this->type == self::T_NUMERIC || $this->type == self::T_INT) {
			if ($this->min !== null && $str < $this->min) {
				$this->errorCode = self::E_MIN;
				return false;
			}
			if ($this->max !== null && $str > $this->max) {
				$this->errorCode = self::E_MAX;
				return false;
			}
		}
		else {
			if (!self::length($str, $this->min)) {
				$this->errorCode = self::E_MIN;
				return false;
			}
			if (!self::length($str, null, $this->max)) {
				$this->errorCode = self::E_MAX;
				return false;
			}
		}

		return true;
	}

	/**
	 * Codiert die Validierungsoptionen als String
	 * @return string
	 */
	public function toString() {
		return ($this->required ? 1 : 0).':'.$this->type.':'.$this->min.':'.$this->max;
	}

	private static $emailBlacklist = array(
		'nurfuerspam.de',
		'nospammail.net',
		'privacy.net',
		'punkass.com',
		'sneakemail.com',
		'bumpymail.com',
		'centermail.com',
		'centermail.net',
		'mailinator.com',
		'discardmail.com',
		'emailias.com',
		'jetable.net',
		'mailexpire.com',
		'mailinator.com',
		'messagebeamer.de',
		'mytrashmail.com',
		'trash-mail.de',
		'trashmail.net',
		'pookmail.com',
		'nervmich.net',
		'netzidiot.de',
		'sofort-mail.de',
		'spamex.com',
		'spamgourmet.com',
		'spamhole.com',
		'spaminator.de',
		'spammotel.com',
		'spamtrail.com',
		'temporaryinbox.com',
		'put2.net',
		'senseless-entertainment.com',
		'fastacura.com',
		'fastchevy.com',
		'fastchrysler.com',
		'fastkawasaki.com',
		'fastmazda.com',
		'fastmitsubishi.com',
		'fastnissan.com',
		'fastsubaru.com',
		'fastsuzuki.com',
		'fasttoyota.com',
		'fastyamaha.com',
		'spam.la',
		'spambob.com',
		'kasmail.com',
		'dumpmail.de',
		'dodgeit.com',
		'guerrillamail.com',
		'jetable.org',
		'emaildienst.de',
		'gomail.ws',
		'temporarily.de');

	/**
	 * Validator Patterns
	 * @var array
	 */
	public static $patterns = [
		'date' => '/^\d{2}\.\d{2}\.\d{4}/',
		'time' => '/^\d{2}\:\d{2}/',
		'datetime' => '/^\d{2}\.\d{2}\.\d{4} \d{2}\:\d{2}/'
	];

	/**
	 * Überprüft ob die übergebene Emailadresse gültig ist.
	 * @param string $email Emailadresse
	 * @param bool $blacklist Soll die Emailadresse auf der blacklist gesucht werden?
	 * @return bool 
	 */
	public static function isEmail($email, $blacklist = true) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return false;

		if ($blacklist && in_array(strtolower(substr($email, strpos($email, '@') + 1)), self::$emailBlacklist))
			return false;

		return true;
	}

	/**
	 * Überprüft ob es sich um eine URL handelt
	 * @param string $str
	 * @return bool 
	 */
	public static function isUrl($str) {
		$str = preg_replace('/[äöü]/i', '', $str);
		return filter_var($str, FILTER_VALIDATE_URL);
	}

	/**
	 * Überprüft ob es sich um eine IP Adresse handelt (ipv4 oder ipv4 oder eines von beiden)
	 * @param string $str
	 * @param bool $ipv4
	 * @param bool $ipv6
	 * @return bool 
	 */
	public static function isIp($str, $ipv4 = true, $ipv6 = true) {
		if ($ipv4 && filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
			return true;
		if ($ipv6 && filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
			return true;

		return false;
	}

	public static function isPhone($str) {
		if (preg_match('/\+[\d]{1,3} [\d]{2,4} [\d ]{4,22}$/', $str))
			return true;

		return false;
	}

	public static function isDate($str) {
		if (preg_match(self::$patterns['date'], $str))
			return true;

		return false;
	}

	public static function isTime($str) {
		if (preg_match(self::$patterns['time'], $str))
			return true;

		return false;
	}

	public static function isDatetime($str) {
		if (preg_match(self::$patterns['datetime'], $str))
			return true;

		return false;
	}

	public static function isNumeric($str) {
		return is_numeric($str);
	}

	public static function isInt($str) {
		if (!preg_match('/-?\d+/', $str))
			return false;

		return true;
	}

	public static function isDomain($str) {
		// FUTURE domain mit angehendem - werden als valid erkannt.....
		if (preg_match('/^([a-z0-9-àáâãäåæçèéêëðìíîïñòóôõöøœþùúûüýÿ]+\.)+([a-z]+)$/i', $str))
			return true;

		return false;
	}

	/**
	 * Überprüft einen String auf dessen Länge 
	 * @param string $str 
	 * @param int $min
	 * @param int $max
	 * @return bool 
	 */
	public static function length($str, $min = null, $max = null) {
		return ((!$min || strlen($str) >= $min) && ($max === null || strlen($str) <= $max));
	}

	/**
	 * Überprüft eine Nummer
	 * @param string $value
	 * @param int $min
	 * @param int $max
	 * @return bool 
	 */
	public static function number($value, $min = null, $max = null) {
		return (($min === null || $value >= $min) && ($max === null || $value <= $max));
	}

	/**
	 * Überprüft ob das Passwort den mindestanforderungen entspricht
	 * @param string $str
	 * @param int $minStrength
	 * @return boolean 
	 */
	public static function isPassword($str, $minStrength = 12) {
		$strength = 0;
		if (strlen($str) >= 6) {
			if (strlen($str) > 8)
				$strength += 2;

			if (strlen($str) > 10)
				$strength += 2;

			if (strlen($str) > 14)
				$strength += 2;

			if (strlen($str) > 18)
				$strength += 2;

			if (strlen($str) > 25)
				$strength += 2;

			if (preg_match('/[a-z]/', $str))
				$strength += 2;

			if (preg_match('/[A-Z]/', $str))
				$strength += 2;

			if (preg_match('/[0-9]/', $str))
				$strength += 4;

			if (preg_match('/\W/', $str))
				$strength += 4;
		}

		return ($strength >= $minStrength);
	}

}
