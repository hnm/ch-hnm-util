<?php
namespace n2nutil\jquery\datepicker;

use n2n\util\StringUtils;

class DatePickerOptions {
	const ATTRIBUTE_PSEUDO = 'data-pseudo';
	const ATTRIBUTE_MONTH_NAMES = 'data-month-names';
	const ATTRIBUTE_MONTH_NAMES_SHORT = 'data-month-names-short';
	const ATTRIBUTE_WEEK_DAYS = 'data-week-days';
	const ATTRIBUTE_WEEK_DAYS_SHORT = 'data-week-days-short';
	const ATTRIBUTE_FIRST_DAY_IN_WEEK = 'data-first-day-in-week';
	const ATTRIBUTE_AM_PM = 'data-am-pm';
	const ATTRIBUTE_TIME_ZONE_PATTERNS = 'data-time-zone-patterns';
	const ATTRIBUTE_PATTERN = 'data-pattern'; 
	
	private $pseudo;
	private $monthNames;
	private $monthNamesShort;
	private $weekDays;
	private $weekDaysShort;
	private $firstDayOfWeek;
	private $amPm;
	private $timeZonePatterns;
	private $pattern;
	
	public function isPseudo() {
		return $this->pseudo;
	}

	public function setPseudo($pseudo) {
		$this->pseudo = $pseudo;
	}

	public function getMonthNames() {
		return $this->monthNames;
	}

	public function setMonthNames($monthNames) {
		$this->monthNames = $monthNames;
	}

	public function getMonthNamesShort() {
		return $this->monthNamesShort;
	}

	public function setMonthNamesShort($monthNamesShort) {
		$this->monthNamesShort = $monthNamesShort;
	}

	public function getWeekDays() {
		return $this->weekDays;
	}

	public function setWeekDays($weekDays) {
		$this->weekDays = $weekDays;
	}

	public function getWeekDaysShort() {
		return $this->weekDaysShort;
	}

	public function setWeekDaysShort($weekDaysShort) {
		$this->weekDaysShort = $weekDaysShort;
	}

	public function getFirstDayOfWeek() {
		return $this->firstDayOfWeek;
	}

	public function setFirstDayOfWeek($firstDayOfWeek) {
		$this->firstDayOfWeek = $firstDayOfWeek;
	}

	public function getAmPm() {
		return $this->amPm;
	}

	public function setAmPm($amPm) {
		$this->amPm = $amPm;
	}

	public function getTimeZonePatterns() {
		return $this->timeZonePatterns;
	}

	public function setTimeZonePatterns($timeZonePatterns) {
		$this->timeZonePatterns = $timeZonePatterns;
	}

	public function getPattern() {
		return $this->pattern;
	}

	public function setPattern($pattern) {
		$this->pattern = $pattern;
	}

	public function buildHtmlAttrs() {
		$attrs = array();
		if (null !== $this->pseudo) {
			$attrs[self::ATTRIBUTE_PSEUDO] = $this->isPseudo();
		}
		
		if (null != $this->monthNames) {
			$attrs[self::ATTRIBUTE_MONTH_NAMES] = StringUtils::jsonEncode($this->monthNames);
		}
		
		if (null != $this->monthNamesShort) {
			$attrs[self::ATTRIBUTE_MONTH_NAMES_SHORT] = StringUtils::jsonEncode($this->monthNamesShort);
		}
		
		if (null != $this->weekDays) {
			$attrs[self::ATTRIBUTE_WEEK_DAYS] = StringUtils::jsonEncode($this->weekDays);
		}
		
		if (null != $this->weekDaysShort) {
			$attrs[self::ATTRIBUTE_WEEK_DAYS_SHORT] = StringUtils::jsonEncode($this->weekDaysShort);
		}
		
		if (null != $this->firstDayOfWeek) {
			$attrs[self::ATTRIBUTE_FIRST_DAY_IN_WEEK] = StringUtils::jsonEncode($this->firstDayOfWeek);
		}
		
		if (null != $this->amPm) {
			$attrs[self::ATTRIBUTE_AM_PM] = StringUtils::jsonEncode($this->amPm);
		}
		
		if (null != $this->timeZonePatterns) {
			$attrs[self::ATTRIBUTE_TIME_ZONE_PATTERNS] = StringUtils::jsonEncode($this->timeZonePatterns);
		}
		
		if (null != $this->pattern) {
			$attrs[self::ATTRIBUTE_PATTERN] = $this->pattern;
		}
		return $attrs;
	}
}

