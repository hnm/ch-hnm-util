<?php
namespace n2nutil\jquery\datepicker\mag;

use n2n\impl\web\dispatch\mag\model\DateTimeMag;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\web\ui\UiComponent;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2nutil\jquery\datepicker\DatePickerHtmlBuilder;

class DateTimePickerMag extends DateTimeMag {

	const DATEPICKER_OPENER_CLASS = 'n2nutil-jquery-datepicker-opener';

	private $addonUiElement;

	public function __construct($label, UiComponent $addonUiElement = null, $dateStyle = null, $timeStyle = null, $icuPattern = null, $value = null, $mandatory = false, $inputAttrs = null) {
		parent::__construct($label, $dateStyle, $timeStyle, $icuPattern, $value, $mandatory, $inputAttrs);
		$this->addonUiElement = $addonUiElement;
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uiOutfitter): UiComponent {
		$datePickerAttrs = HtmlUtils::mergeAttrs($uiOutfitter->createAttrs(
			UiOutfitter::NATURE_TEXT|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);

		$dpHtml = new DatePickerHtmlBuilder($view);
		if (null === $this->addonUiElement) {
			return $dpHtml->getFormDatePicker($propertyPath, $datePickerAttrs);
		}

		$datePickerAttrs = HtmlUtils::mergeAttrs($datePickerAttrs,
				array('data-selector-opener' => '.' . self::DATEPICKER_OPENER_CLASS));

		$addonWrapperElement = $uiOutfitter->createElement(UiOutfitter::EL_NATURE_CONTROL_ADDON_WRAPPER,
				array('class' => self::DATEPICKER_OPENER_CLASS), $this->addonUiElement);

		return $uiOutfitter->createElement(UiOutfitter::EL_NATRUE_CONTROL_ADDON_SUFFIX_WRAPPER,
				null, array($dpHtml->getFormDatePicker($propertyPath, $datePickerAttrs), $addonWrapperElement));
	}
}