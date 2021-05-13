<?php
namespace n2nutil\jquery\datepicker\pseudo;

use n2nutil\jquery\datepicker\DatePickerOptionsFactory;
use n2nutil\jquery\datepicker\DatePickerOptions;
use n2nutil\jquery\datepicker\DateTimeLiteralGenerator;
class DefaultDatePickerOptionsFactory implements DatePickerOptionsFactory {

	public function createDatePickerOptions($pattern) {
		$dateTimeLiteralGenerator = new DateTimeLiteralGenerator();
		$patternChecker = new DefaultPatternChecker($pattern);
		$datePickerOptions = new DatePickerOptions();
		
		$datePickerOptions->setPseudo(true);
		$datePickerOptions->setMonthNames($dateTimeLiteralGenerator->generateMonthNames());
		$datePickerOptions->setMonthNamesShort($dateTimeLiteralGenerator->generateMonthNamesShort());
		$datePickerOptions->setWeekDaysShort($dateTimeLiteralGenerator->generateWeekDaysShort());
		$datePickerOptions->setTimeZonePatterns($this->getTimeZonePatterns($patternChecker));
		
		//Weekdays & Am-Pm are not mandatory for the DatePicker (just if it is needed to format the Date)
		if ($patternChecker->weekDaysNeeded()) {
			$datePickerOptions->setWeekDays($dateTimeLiteralGenerator->generateWeekDays());
		}
		
		if ($patternChecker->amPmNeeded()) {
			$datePickerOptions->setAmPm($dateTimeLiteralGenerator->generateAmPm());
		}
		$datePickerOptions->setPattern($pattern);
		return $datePickerOptions;
	}

	private function getTimeZonePatterns(DefaultPatternChecker $patternChecker) {
		if (null == ($timeZonePatternParts = $patternChecker->getTimeZonePatternParts())) return null;
		
		$timeZonePatterns = array();
		foreach ($timeZonePatternParts as $patternPart) {
			$now = new \DateTime();
			$timeZonePatterns[$patternPart] = $now->format($patternPart);
		}
		return $timeZonePatterns;
	}
	
}
