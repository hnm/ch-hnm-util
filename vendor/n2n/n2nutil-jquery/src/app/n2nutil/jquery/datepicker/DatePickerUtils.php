<?php
namespace n2nutil\jquery\datepicker;

use n2n\l10n\DateTimeFormat;
use n2n\l10n\L10nUtils;
use n2nutil\jquery\datepicker\l10n\IcuDatePickerOptionsFactory;
use n2n\l10n\N2nLocale;
use n2nutil\jquery\datepicker\pseudo\DefaultDatePickerOptionsFactory;
use n2n\l10n\L10n;

class DatePickerUtils {
	public static function determinePattern($locale, $dateStyle = DateTimeFormat::DEFAULT_DATE_STYLE, $timeStyle = null,
			\DateTimeZone $timeZone = null, $simpleFormat = null) {
		if (null !== $simpleFormat) return $simpleFormat;
		if (null === $timeStyle) return DateTimeFormat::createDateInstance($locale, $dateStyle, $timeZone)->getPattern();
		return DateTimeFormat::createDateTimeInstance($locale, $dateStyle, $timeStyle, $timeZone)->getPattern();
	}
	/**
	 * @param N2nLocale $locale
	 * @return \n2nutil\jquery\datepicker\DatePickerOptionsFactory
	 */
	public static function getDatePickerOptionsFactory(N2nLocale $locale, \DateTimeZone $timeZone = null) {
		if (L10n::getL10nConfig()->isEnabled()) {
			L10nUtils::ensureL10nsupportIsAvailable();
			return new IcuDatePickerOptionsFactory($locale, $timeZone);
		} else {
			return new DefaultDatePickerOptionsFactory();
		}
	}
}
