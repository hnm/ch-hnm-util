<?php
namespace n2nutil\jquery\datepicker\pseudo;

class DefaultPatternChecker {
	
	const SPLIT_PATTERN = '/(\\.|.)/';
	
	private $matches;
	
	public function __construct($pattern) {
		$matches = array();
		preg_match_all(self::SPLIT_PATTERN, $pattern, $matches);
		$this->matches = (array) reset($matches);
	}
	
	public function weekDaysNeeded() {
		return $this->hasPatternPartsInMatches(array('l'));
	}
	
	public function amPmNeeded() {
		return $this->hasPatternPartsInMatches(array('a', 'A'));
	}
	
	public function getTimeZonePatternParts() {
		if (count(($timeZonePatternParts = $this->getPatternPartsInMatches(array('e', 'O', 'P', 'T'))))) {
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
