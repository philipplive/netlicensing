<?php
namespace HfCore;

/**
 * Time
 */
class Time {
	/**
	 * Jetzt
	 * @return \DateTime
	 */
	public static function now(): \DateTime {
		return new \DateTime();
	}

	/**
	 * Heute um 00:00:00
	 * @return DateTime
	 */
	public static function today(): \DateTime {
		return self::now()->setTime(0, 0, 0);
	}

	/**
	 * Morgen um 00:00:00
	 * @return DateTime
	 */
	public static function tomorrow(): \DateTime {
		return self::now()->setTime(0, 0, 0)->add(new \DateInterval('P1D'));
	}

	/**
	 * Gestern um 00:00:00
	 * @return DateTime
	 */
	public static function yesterday(): \DateTime {
		return self::now()->setTime(0, 0, 0)->sub(new \DateInterval('P1D'));
	}

	/**
	 * Aus Timestamp
	 * @param int $unixtimestamp
	 * @return DateTime
	 */
	public static function fromTimestamp(int $unixtimestamp): \DateTime {
		$time = new \DateTime();
		return $time->setTimestamp($unixtimestamp);
	}

	/**
	 * DateTime von Heute minus DateInterval
	 * @param DateInterval|string $interval DateInterval
	 * @return DateTime
	 */
	public static function goBack($interval): \DateTime {
		if (is_string($interval))
			$interval = new \DateInterval($interval);

		$limit = new \DateTime();
		$limit->sub($interval);
		return $limit;
	}

	/**
	 * DateTime von Heute plus DateInterval
	 * @param \DateInterval|string $interval \DateInterval
	 * @return \DateTime
	 */
	public static function goForward($interval): \DateTime {
		if (is_string($interval))
			$interval = new \DateInterval($interval);

		$limit = new \DateTime();
		$limit->add($interval);
		return $limit;
	}
}
