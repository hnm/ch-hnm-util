<?php
namespace n2nutil\jquery\datepicker;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\l10n\N2nLocale;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\l10n\DateTimeFormat;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;
use n2nutil\jquery\JQueryLibrary;

class DatePickerHtmlBuilder {
	private $view; 
	
	public function __construct(HtmlView $view) {
		$this->view = $view;
	}
	
	public function getDatePicker($dateStyle = DateTimeFormat::DEFAULT_DATE_STYLE, $timeStyle = null, 
			\DateTimeZone $timeZone = null, $simpleFormat = null, $attrs = null, N2nLocale $locale = null) {
		if (null == $locale) {
			$locale = $this->view->getRequest()->getN2nLocale();
		}
		$attrs = $this->extendAttrs($attrs);
		$this->requireScripts($attrs['id']);
		return new HtmlElement('input', HtmlUtils::mergeAttrs((array) $attrs, DatePickerUtils::getDatePickerOptionsFactory(
					$locale, $timeZone)->createDatePickerOptions(DatePickerUtils::determinePattern($locale, $dateStyle, $timeStyle, 
							$timeZone, $simpleFormat))->buildHtmlAttrs()));
	}
	
	public function datePicker($dateStyle = DateTimeFormat::DEFAULT_DATE_STYLE, $timeStyle = null, 
			\DateTimeZone $timeZone = null, $simpleFormat = null, $attrs = null, N2nLocale $locale = null) {
		$this->view->out($this->getDatePicker($dateStyle, $timeStyle, $timeZone, $simpleFormat, $attrs, $locale));
	}
	
	public function getFormDatePicker($propertyExpression = null, $attrs = null) {
		$attrs = $this->extendAttrs($attrs);
		$this->requireScripts();
		
		$factory = new InputDatePickerFactory($this->view->getHtmlProperties()->getForm());
		return $factory->create($this->view->getFormHtmlBuilder()->meta()->createPropertyPath($propertyExpression), $attrs);
	}
	
	public function formDatePicker($propertyExpression = null, $attrs = null) {
		$this->view->out($this->getFormDatePicker($propertyExpression, $attrs));
	}
	
	private function extendAttrs($attrs) {
		return HtmlUtils::mergeAttrs((array) $attrs, array('class' => 'util-jquery-datepicker'));
	}
	
	private function requireScripts() {
		$html = $this->view->getHtmlBuilder();
		$html->meta()->addLibrary(new JQueryLibrary(3));
		$html->meta()->bodyEnd()->addJs('js/ajah.js', 'n2n\impl\web\ui');
		$html->meta()->addJs('datepicker/js/datePicker.js', 'n2nutil\jquery', false, false, null, HtmlBuilderMeta::TARGET_BODY_END);
		$html->meta()->addCss('datepicker/css/datePicker.css', 'screen', 'n2nutil\jquery');
	}
}