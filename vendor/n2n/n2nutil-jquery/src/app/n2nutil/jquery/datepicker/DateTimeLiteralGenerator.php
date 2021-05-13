<?php
namespace n2nutil\jquery\datepicker;

use n2n\l10n\SimpleDateTimeFormat;
use n2n\l10n\N2nLocale;
class DateTimeLiteralGenerator {
	
	const ICU_WEEK_DAY_PATTERN = 'EEEE';
	const ICU_WEEK_DAY_SHORT_PATTERN = 'E';
	const ICU_MONTH_NAME_PATTERN = 'MMMM';
	const ICU_MONTH_NAME_SHORT_PATTERN = 'MMM';
	const ICU_AM_PM_PATTERN = 'a';
	
	const DEFAULT_WEEK_DAY_PATTERN = 'l';
	const DEFAULT_WEEK_DAY_SHORT_PATTERN = 'D';
	const DEFAULT_MONTH_NAME_PATTERN = 'F';
	const DEFAULT_MONTH_NAME_SHORT_PATTERN = 'M';
	const DEFAULT_AM_PM_PATTERN = 'a';
	const DEFAULT_NUMERIC_DAY_OF_WEEK = 'w';
	
	private $locale;
	
	private $sunday;
	private $monday;
	private $tuesday;
	private $wednesday;
	private $thursday;
	private $friday;
	private $saturday;
	
	public function __construct(N2nLocale $locale = null) {
		$this->locale = $locale; 
		$this->sunday = \DateTime::createFromFormat('j.n.Y', '29.12.2013');
		$this->monday = \DateTime::createFromFormat('j.n.Y', '30.12.2013');
		$this->tuesday = \DateTime::createFromFormat('j.n.Y', '31.12.2013');
		$this->wednesday = \DateTime::createFromFormat('j.n.Y', '1.1.2014');
		$this->thursday = \DateTime::createFromFormat('j.n.Y', '16.8.1984');
		$this->friday = \DateTime::createFromFormat('j.n.Y', '21.12.1984');
		$this->saturday = \DateTime::createFromFormat('j.n.Y', '7.9.1985');
		$this->am = new \DateTime();
		$this->am->setTime(8,0,0);
		$this->pm = new \DateTime();
		$this->pm->setTime(20,0,0);
	}
	
	public function generateWeekDays() {
		$defaultValues = $this->getDefaultWeekDays();
		if (null === $this->locale) {
			return $defaultValues;
		}
		return $this->defaultToIcu($defaultValues, self::DEFAULT_WEEK_DAY_PATTERN, 
				self::ICU_WEEK_DAY_PATTERN);
	}
	
	public function generateWeekDaysShort() {
		$defaultValues = $this->getDefaultWeekDaysShort();
		if (null === $this->locale) {
			return $defaultValues;
		}
		return $this->defaultToIcu($defaultValues, self::DEFAULT_WEEK_DAY_SHORT_PATTERN, 
				self::ICU_WEEK_DAY_SHORT_PATTERN);
	}
	
	public function generateMonthNames() {
		$defaultValues = $this->getDefaultMonthNames();
		if (null === $this->locale) {
			return $defaultValues;
		}
		return $this->getIcuMonthNames();
	}
	
	public function generateMonthNamesShort() {
		$defaultValues = $this->getDefaultMonthNamesShort();
		if (null === $this->locale) {
			return $defaultValues;
		}
		return $this->getIcuMonthNamesShort();
	}
	public function generateAmPm() {
		$defaultValues = $this->getDefaultAmPm();
		if (null === $this->locale) {
			return $defaultValues;
		}
		$icuValues = array();
		// we need to add a hour to the am/pm pattern otherwise it isn't possible to get the icu values
		$simpleDateFormat = new SimpleDateTimeFormat($this->locale, self::ICU_AM_PM_PATTERN);
		foreach ($this->getDefaultAmPm() as $value) {
			$icuValues[] = $simpleDateFormat->format(\DateTime::createFromFormat('h ' . self::DEFAULT_AM_PM_PATTERN, '12 ' . $value));
		}
		return $icuValues;
	}
	
	public function getDefaultWeekDays() {
		return array($this->sunday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->monday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->tuesday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->wednesday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->thursday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->friday->format(self::DEFAULT_WEEK_DAY_PATTERN), 
				$this->saturday->format(self::DEFAULT_WEEK_DAY_PATTERN));
	}
	
	public function getDefaultWeekDaysShort() {
		return array($this->sunday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->monday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->tuesday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->wednesday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->thursday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->friday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN), 
				$this->saturday->format(self::DEFAULT_WEEK_DAY_SHORT_PATTERN));
	}
	
	public function getDefaultMonthNames() {
		$monthNames = array();
		$date = new \DateTime();
		for ($i = 1; $i <= 12; $i++) {
			$date->setDate($date->format('Y'), $i, 1);
			$monthNames[] = $date->format(self::DEFAULT_MONTH_NAME_PATTERN);
		}
		return $monthNames;
	}
	
	public function getIcuMonthNames() {
		$monthNames = array();
		$date = new \DateTime();
		$simpleDateFormat = new SimpleDateTimeFormat($this->locale, self::ICU_MONTH_NAME_PATTERN);
		for ($i = 1; $i <= 12; $i++) {
			$date->setDate($date->format('Y'), $i, 1);
			$monthNames[] = $simpleDateFormat->format($date);
		}
		return $monthNames;
	}
	
	public function getDefaultMonthNamesShort() {
		$monthNames = array();
		$date = new \DateTime();
		for ($i = 1; $i <= 12; $i++) {
			$date->setDate($date->format('Y'), $i, 1);
			$monthNames[] = $date->format(self::DEFAULT_MONTH_NAME_SHORT_PATTERN);
		}
		return $monthNames;
	}
	
	public function getIcuMonthNamesShort() {
		$monthNames = array();
		$date = new \DateTime();
		$simpleDateFormat = new SimpleDateTimeFormat($this->locale, self::ICU_MONTH_NAME_SHORT_PATTERN);
		for ($i = 1; $i <= 12; $i++) {
			$date->setDate($date->format('Y'), $i, 1);
			$monthNames[] = $simpleDateFormat->format($date);
		}
		return $monthNames;
	}
	
	public function getDefaultAmPm() {
		return array($this->am->format(self::DEFAULT_AM_PM_PATTERN), $this->pm->format(self::DEFAULT_AM_PM_PATTERN));
	}

	private function defaultToIcu(array $defaultValues, $defaultPattern, $icuPattern) {
		$icuValues = array();
		$simpleDateFormat = new SimpleDateTimeFormat($this->locale, $icuPattern);
		foreach ($defaultValues as $value) {
			$icuValues[] = $simpleDateFormat->format(\DateTime::createFromFormat($defaultPattern, $value));
		}
		return $icuValues;
	}
}