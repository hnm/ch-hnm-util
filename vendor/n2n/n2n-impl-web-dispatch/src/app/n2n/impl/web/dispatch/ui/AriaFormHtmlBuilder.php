<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\web\dispatch\ui;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\l10n\DynamicTextCollection;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\web\dispatch\Dispatchable;

class AriaFormHtmlBuilder {
	const ERROR_PREFIX = 'err-';
	/**
	 * @var FormHtmlBuilder
	 */
	private $formHtml;
	private $datePickerHtml;
	private $autoCompletionHtml;
	private $view;
	private $dtc;

	public function __construct(HtmlView $view) {
		$this->formHtml = $view->getFormHtmlBuilder();
		$this->view = $view;
		$this->dtc = new DynamicTextCollection('n2n\impl\web\dispatch', $view->getN2nContext()->getN2nLocale());
	}
	
	public function open(Dispatchable $dispatchable, string $enctype = null, $method = null, 
			array $attrs = null, $action = null) {
		$this->formHtml->open($dispatchable, $enctype, $method, $attrs, $action);
	}
	
	public function close() {
		$this->formHtml->close();
	}

	public function label($forPropertyExpression = null, bool $required = false, $label = null, array $attrs = null) {
		$this->view->out($this->getLabel($forPropertyExpression, $required, $label, $attrs));
	}
	
	public function getLabel($forPropertyExpression = null, bool $required = false, $label = null, array $attrs = null) {
		if ($required) {
			if ($label === null) {
				$label = $this->formHtml->meta()->getLabel($forPropertyExpression);
			}
			
			$label = new HtmlSnippet($label, PHP_EOL, new HtmlElement('abbr', 
					array('title' => $this->dtc->translate('aria_required_label')), '*'));
		}
		
		return $this->formHtml->getLabel($forPropertyExpression, $label, $attrs);
	}


	public function input($propertyExpression = null, bool $required = false, array $attrs = null, $type = 'text',
			$secret = false, $fixedValue = null, $tagName = 'input', $elementContents = null) {
		$this->view->out($this->getInput($propertyExpression, $required, $attrs, $type, $secret,
				$fixedValue, $tagName, $elementContents));
	}
	
	public function getInput($propertyExpression = null, bool $required = false, array $attrs = null, $type = 'text',
			$secret = false, $fixedValue = null, $tagName = 'input', $elementContents = null) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, $required);
		return $this->formHtml->getInput($propertyExpression, $attrs, $type, $secret,
				$fixedValue, $tagName, $elementContents);
	}


	public function textArea($propertyExpression = null, bool $required = false, array $attrs = null) {
		$this->view->out($this->getTextarea($propertyExpression, $required, $attrs));
	}
	
	public function getTextarea($propertyExpression = null, bool $required = false, array $attrs = null) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, $required);
		return $this->formHtml->getTextarea($propertyExpression, $attrs);
	}

	public function inputRadio($propertyExpression = null, $value = true, array $attrs = null, $label = null, 
			array $labelAttrs = null) {
		$this->view->out($this->getInputRadio($propertyExpression, $value, $attrs, $label, $labelAttrs));
	}

	public function getInputRadio($propertyExpression = null, $value = true, array $attrs = null, $label = null, 
			array $labelAttrs = null) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, null);
		return $this->formHtml->getInputRadio($propertyExpression, $value, $attrs, $label, $labelAttrs);
	}

	public function select($propertyExpression, array $options, bool $required = false, array $attrs = null, 
			bool $multiple = false) {
		$this->view->out($this->getSelect($propertyExpression, $options, $required, $attrs, $multiple));
	}

	public function getSelect($propertyExpression, array $options, bool $required = false, array $attrs = null, 
			bool $multiple = false) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, $required);
		return $this->formHtml->getSelect($propertyExpression, $options, $attrs, $multiple);
	}
	
	public function inputCheckbox($propertyExpression = null, $value = true, bool $required = false, 
			array $attrs = null, $label = null, array $labelAttrs = null) {
		$this->view->out($this->getInputCheckbox($propertyExpression, $value, $required, $attrs, $label, $labelAttrs));
	}
	
	public function getInputCheckbox($propertyExpression = null, $value = true, bool $required = false, 
			array $attrs = null, $label = null, array $labelAttrs = null) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, $required);
		return $this->formHtml->getInputCheckbox($propertyExpression, $value, $attrs, $label, $labelAttrs);
	}

	public function inputFileWithLabel($propertyExpression = null, bool $required = false, array $attrs = null, array $labelAttrs = null) {
		$this->view->out($this->getInputFileWithLabel($propertyExpression, $required, $attrs, $labelAttrs));
	}
	
	public function getInputFileWithLabel($propertyExpression = null, bool $required = false, array $attrs = null, array $labelAttrs = null) {
		$attrs = $this->ariafyAttrs($propertyExpression, $attrs, $required);
		return $this->formHtml->getInputFileWithLabel($propertyExpression, $attrs, $labelAttrs);
	}

	private function ariafyAttrs($propertyExpression = null, array $customAttrs = null, bool $required = null) {
		$attrs = array();
		if ($required !== null) {
			$attrs['aria-required'] = $required ? "true" : "false";
		}
		if ($this->formHtml->meta()->hasErrors($propertyExpression)) {
			$attrs['aria-invalid'] = 'true';
			$attrs['aria-describedby'] = $this->createErrorId($propertyExpression);
		}
		return HtmlUtils::mergeAttrs($attrs, (array) $customAttrs);
	}

	public function message($propertyExpression = null, string $containerTagName = 'div', array $containerAttrs = null) {
		$this->view->out($this->getMessage($propertyExpression, $containerTagName, $containerAttrs));
	}
	
	public function getMessage($propertyExpression = null, string $containerTagName = 'div', array $containerAttrs = null,
			bool $recursive = true, bool $markAsProcessed = true, bool $unprocessedOnly = true) {
		$containerAttrs = HtmlUtils::mergeAttrs(array('id' => $this->createErrorId($propertyExpression)), 
				(array) $containerAttrs);
		return $this->formHtml->getMessage($propertyExpression, $containerTagName, $containerAttrs, 
				$recursive, $markAsProcessed, $unprocessedOnly);
	}

	private function createErrorId($propertyExpression = null) {
		$propertyPath = $this->formHtml->meta()->createPropertyPath($propertyExpression);
		return self::ERROR_PREFIX . implode('-', $propertyPath->toArray());
	}
}
