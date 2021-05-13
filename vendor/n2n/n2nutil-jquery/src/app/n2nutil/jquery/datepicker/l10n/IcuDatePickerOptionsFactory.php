<?php
namespace n2nutil\jquery\datepicker\l10n;

use n2nutil\jquery\datepicker\DateTimeLiteralGenerator;
use n2n\l10n\SimpleDateTimeFormat;
use n2n\l10n\N2nLocale;
use n2nutil\jquery\datepicker\DatePickerOptionsFactory;
use n2nutil\jquery\datepicker\DatePickerOptions;

class IcuDatePickerOptionsFactory implements DatePickerOptionsFactory {
	const ICU_LOCALE_DAY_OF_WEEK_PATTERN = 'e';
	
	private $icuLiteralGenerator;
	private $timeZone;
	private $locale; 
	
	public function __construct(N2nLocale $locale, \DateTimeZone $timeZone = null) {
		$this->locale = $locale;
		$this->icuLiteralGenerator = new DateTimeLiteralGenerator($locale);
		$this->timeZone = $timeZone;	
	}
	
	public function createDatePickerOptions($pattern) {
		$patternChecker = new IcuPatternChecker($pattern);
		$datePickerOptions = new DatePickerOptions();
		
		$datePickerOptions->setPseudo(false);
		$datePickerOptions->setMonthNames($this->icuLiteralGenerator->generateMonthNames());
		$datePickerOptions->setMonthNamesShort($this->icuLiteralGenerator->generateMonthNamesShort());
		$datePickerOptions->setWeekDaysShort($this->icuLiteralGenerator->generateWeekDaysShort());
		$datePickerOptions->setTimeZonePatterns($this->getTimeZonePatterns($patternChecker));
		$datePickerOptions->setFirstDayOfWeek($this->getFirstDayOfWeek());
		$datePickerOptions->setPattern($pattern);
		
		//Weekdays & Am-Pm are not mandatory for the DatePicker (just if it is needed to format the Date)
		if ($patternChecker->weekDaysNeeded()) {
			$datePickerOptions->setWeekDays($this->icuLiteralGenerator->generateWeekDays());
		}
		
		if ($patternChecker->amPmNeeded()) {
			$datePickerOptions->setAmPm($this->icuLiteralGenerator->generateAmPm());
		}
		return $datePickerOptions;
	}
	
	private function getTimeZonePatterns(IcuPatternChecker $patternChecker) {
		if (null == ($timeZonePatternParts = $patternChecker->getTimeZonePatternParts())) return null;
		$timeZonePatterns = array();
		foreach ($timeZonePatternParts as $patternPart) {
			$simpleDateTimeFormat = new SimpleDateTimeFormat($this->locale, $patternPart, $this->timeZone);
			$timeZonePatterns[$patternPart] = $simpleDateTimeFormat->format(new \DateTime());
		}
		return $timeZonePatterns;
	}
	
	private function getFirstDayOfWeek() {
		foreach ($this->icuLiteralGenerator->getDefaultWeekDays() as $key => $weekDay) {
			$dateTimeFormat = new SimpleDateTimeFormat($this->locale, self::ICU_LOCALE_DAY_OF_WEEK_PATTERN, $this->timeZone);
			if (intval($dateTimeFormat->format(\DateTime::createFromFormat(DateTimeLiteralGenerator::DEFAULT_WEEK_DAY_PATTERN, $weekDay)) == 1)) {
				return $key;
			}
		}
	}
}