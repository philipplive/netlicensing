<?php

/**
 * HTTP Query Manager
 */
class Query {
	/**
	 * Parameter aus Array lesen
	 * @param array $data Array
	 * @param string $index Index
	 * @param string $type Variablentyp (HFCore\T_STR, HFCore\T_BOOL, HFCore\T_INT, HFCore\T_ARR)
	 * @param mixed $default Standardwert, falls nicht gesetzt
	 * @return mixed
	 */
	public static function param($data, string $index, string $type = HFCore\T_STR, $default = null) {
		// WP Fix, da leere Paramater ein string anstelle eines Arrays sind...
		if(!is_array($data))
			$data = [];

		switch ($type) {
			case HFCore\T_BOOL:
				return isset($data[$index]) ? ($data[$index] == '1' || $data[$index] == 'true') : (($default !== null) ? $default : false);
			case HFCore\T_INT:
				return isset($data[$index]) ? (int)$data[$index] : (($default !== null) ? $default : 0);
			case HFCore\T_DOUBLE:
				return isset($data[$index]) ? (double)$data[$index] : (($default !== null) ? $default : 0.0);
			case HFCore\T_STR:
			case HFCore\T_STRING:
				return isset($data[$index]) ? $data[$index] : (($default !== null) ? $default : '');
			case HFCore\T_ARR:
			case HFCore\T_ARRAY:
				$return = isset($data[$index]) ? $data[$index] : (($default !== null) ? $default : []);
				if (!is_array($return))
					return [];

				return $return;
		}

		throw new \Exception('Unbekannter Datentyp angegeben');
	}

}
