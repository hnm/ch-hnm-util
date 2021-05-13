<?php
namespace n2nutil\jquery\autocomplete\ui;

use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;

class AutocompletionHtmlBuilder {
	private $view;
	
	public function __construct(HtmlView $view) {
		$this->view = $view;
	}
	
	public function getFormAutocomplete($propertyExpression = null, $attrs = null, array $options = null) {
		$this->requireScripts();
		$attrs = $this->extendAttrs($attrs);
		if (null !== $options) {
			return $this->view->getFormHtmlBuilder()->getSelect($propertyExpression, $options, $attrs);
		}
		return $this->view->getFormHtmlBuilder()->getInput($propertyExpression, $attrs);
	}
	
	public function formAutocomplete($propertyExpression = null, $attrs = null, array $options = null) {
		$this->view->out($this->getFormAutocomplete($propertyExpression, $attrs, $options));
	}
	
	private function extendAttrs($attrs) {
		return HtmlUtils::mergeAttrs((array) $attrs, array('class' => 'util-jquery-autocomplete'));
	}
	
	private function requireScripts() {
		$html = $this->view->getHtmlBuilder();
		//@todo add jQueryLibrary
		//$html->meta()->addLibrary(new JQueryLibrary());
		$html->meta()->addJs('autocomplete/js/autocomplete.js', 'n2nutil\jquery', false, false, null, HtmlBuilderMeta::TARGET_BODY_END);
	}
}