<?php
namespace HfCore;

class Cronjob {
	/**
	 * @var array [] => [cronjobName, timing];
	 */
	private $crons = [];

	public function __construct() {
		add_action('wp', [$this, 'schedule']);
	}

	/**
	 * Cronjob hinzufügen
	 * @param string $cronjobName
	 * @param $callback z.B. test-cronjob
	 * @param string $timing  ‘hourly’, ‘daily’, and ‘twicedaily’.
	 */
	public function addCronjob(string $cronjobName, $callback, string $timing = 'daily') {
		$this->crons[] = [$cronjobName, $timing];

		add_action($cronjobName, $callback);
	}

	/**
	 * Registrierten Cronjob wieder löschen
	 * @param string $cronjobName
	 */
	public function removeCronjob(string $cronjobName) {
		wp_unschedule_event(wp_next_scheduled($cronjobName), $cronjobName);
	}

	/**
	 * Alle Cronjobs löschen
	 */
	public function removeAll(){
		foreach ($this->crons as $cron) {
			$this->removeCronjob($cron[0]);
		}
	}

	public function schedule() {
		foreach ($this->crons as $cron) {
			if (!wp_next_scheduled($cron[0])) {
				wp_schedule_event(time(), $cron[1], $cron[0]);
			}
		}
	}
}