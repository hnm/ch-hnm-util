<?php

namespace n2nutil\jquery\datepicker\l10n;

class IcuPatternChecker {
	
	const SPLIT_PATTERN = '/(\'.|G{2,5}|yy?yy|M{2,5}|(e|E){2,6}|dd|hh|HH|mm|ss|z{4}|.)/';
	
	private $matches;
	
	public function __construct($pattern) {
		$matches = array();
		preg_match_all(self::SPLIT_PATTERN, $pattern, $matches);
		$this->matches = (array) reset($matches);
	}
	
	public function weekDaysNeeded() {
		return $this->hasPatternPartsInMatches(array('EEEE', 'EEEEE', 'EEEEEE', 'eeee', 'eeeee', 'eeeeee'));
	}
	
	public function amPmNeeded() {
		return $this->hasPatternPartsInMatches(array('a'));
	}
	
	public function getTimeZonePatternParts() {
		if (count(($timeZonePatternParts = $this->getPatternPartsInMatches(array('z', 'zzzz'))))) {
			return $timeZonePatternParts;
		}
		return null;
	}
	
	private function getPatternPartsInMatches(array $patternParts) {
		$patternParts = array();
		foreach($this->matches as $match) {
			if (!in_array($match, $patternParts) || in_array($match, $patternParts)) continue;
			$patternParts[] = $match;
		}
		return $patternParts;
	}

	private function hasPatternPartsInMatches(array $patternParts) {
		foreach($this->matches as $match) {
			if (!in_array($match, $patternParts)) continue;
			return true;
		}
		return false;
	}
}
