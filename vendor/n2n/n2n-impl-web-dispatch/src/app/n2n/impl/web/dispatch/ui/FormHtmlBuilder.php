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

use n2n\impl\web\dispatch\mag\model\BasicUiOutfitter;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\Dispatchable;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\web\http\Method;
use n2n\impl\web\ui\view\html\MessageList;
use n2n\web\ui\Raw;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\util\ex\NotYetImplementedException;
use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\DispatchContext;

class FormHtmlBuilder {
	private $view;
	private $formCreated = false;
	private $meta;
	
	private $factory;
	private $magStack = array();
// 	private $basePaths = array();
// 	private $optionStack;
	
	const ARRAY_CLOSURE_KEY_PARAM = 'key';
	const ARRAY_CLOSURE_VALUE_PARAM = 'value';
// 	const ARRAY_CLOSE_EXISTS_PARAM = 'exists';
// 	const OBJECT_CLOSE_PROPERTY_PARAM = 'propertyName';

	/**
	 * @param HtmlView $view
	 */
	public function __construct(HtmlView $view) {
		$this->view = $view;
		$this->meta = new FormHtmlBuilderMeta($view);
	}
	
	/**
	 * @return \n2n\impl\web\dispatch\ui\FormHtmlBuilderMeta
	 */
	public function meta() {
		return $this->meta;
	}
	
	public function open(Dispatchable $dispatchable, string $enctype = null, $method = null, 
			array $attrs = null, $action = null) {
		$form = $this->view->getHtmlProperties()->getForm();
		if ($form !== null) {
			throw new IllegalFormStateException('Form already open.');
		}
		
		if ($method === null) $method = Method::POST;
		
		$dispatchContext = $this->view->getN2nContext()->lookup(DispatchContext::class);
		$form = new Form($this->view, $dispatchContext->getOrCreateMappingResult($dispatchable, 
						$this->view->getN2nContext()), 
				$method, $enctype);
		$this->view->getHtmlProperties()->setForm($form);
		
		$this->formCreated = true;
		
		if ($action === null) {
			$action = $this->view->getRequest()->getRelativeUrl();
		}
		$form->printOpenTag($this->view->getActiveBuffer(), $action, $attrs);		
	}
	
	public function close() {
		$form = $this->meta->getForm();
		
		if (!$this->formCreated) {
			throw new IllegalFormStateException('Can not close form which was opened in a parent view.');
		}
		
		
		$attrs = $form->getDispatchTargetEncoder()->encodeDispatchTarget($form->getDispatchTarget());
		$attrs['type'] = 'hidden';
		$this->view->out(new HtmlElement('input', $attrs));

		$form->printCloseTag($this->view);
		$this->view->getHtmlProperties()->setForm(null);
		$this->factory = null;
		$this->formCreated = false;
	}
	
	public function openPseudo(Dispatchable $dispatchable, PropertyPath $propertyPath) {
		$form = $this->view->getHtmlProperties()->getForm();
		if ($form !== null) {
			throw new IllegalFormStateException('Form already open.');
		}

		$dispatchContext = $this->view->getN2nContext()->lookup(DispatchContext::class);
		$form = null;
// 		try {
			$form = new Form($this->view, $dispatchContext->getOrCreateMappingResult($dispatchable,
					$this->view->getN2nContext()), Method::POST, null);
			$form->getDispatchTargetEncoder()->setPseudoBasePropertyPath($propertyPath);
			$this->view->getHtmlProperties()->setForm($form);
// 		} catch (\InvalidArgumentException $e) {
// 			throw $this->view->decorateException($e);
// 		}
		$this->formCreated = true;
	}
	
	public function closePseudo() {
		$form = $this->meta->getForm();
		
		if (!$this->formCreated) {
			throw new IllegalFormStateException('Can not close form which was opened in a parent view.');
		}
				
		$this->view->getHtmlProperties()->setForm(null);
		$this->factory = null;
		$this->formCreated = false;
	}
	
	public function messageList($propertyExpression = null, array $attrs = null, bool $recursive = true) {
		$this->view->out($this->getMessageList($propertyExpression, $attrs, $recursive));
	}
	
	public function getMessageList($propertyExpression = null, array $attrs = null, bool $recursive = true) {
		return new MessageList($this->view->getDynamicTextCollection(), $this->meta->getMessages($propertyExpression, $recursive), $attrs);
	}
	
	public function message($propertyExpression = null, string $containerTagName = 'div', array $containerAttrs = null,
			bool $recursive = true, bool $markAsProcessed = true, bool $unprocessedOnly = true) {
		$this->view->out($this->getMessage($propertyExpression, $containerTagName, $containerAttrs, 
				$recursive, $markAsProcessed, $unprocessedOnly));
	}
	
	public function getMessage($propertyExpression = null, string $containerTagName = 'div', array $containerAttrs = null,
			bool $recursive = true, bool $markAsProcessed = true, bool $unprocessedOnly = true) {
		$messages = $this->meta->getMessages($propertyExpression, $recursive, true, 1, $markAsProcessed, $unprocessedOnly);
		if (empty($messages)) return null;
		
		return new HtmlElement($containerTagName, $containerAttrs, current($messages)->tByDtc($this->view->getDynamicTextCollection()));
	}
	
	public function outOnError($propertyExpression = null, $contents = null) {
		$this->view->out($this->getOutOnError($propertyExpression, $contents));
	}
	
	public function getOutOnError($propertyExpression = null, $contents = null) {
		if ($this->meta()->hasErrors($propertyExpression)) {
			return $this->view->getHtmlBuilder()->getOut($contents);
		}
		
		return null;
	}
	
	private function getFactory() {
		// ensure form is open
		$form = $this->meta->getForm();
		
		if ($this->factory === null) {
			$this->factory = new FormUiComponentFactory($form);
		}
		
		return $this->factory;
	}
	
	public function label($forPropertyExpression = null, $label = null, array $attrs = null) {
		$this->view->out($this->getLabel($forPropertyExpression, $label, $attrs));
	}
	
	public function getLabel($forPropertyExpression = null, $label = null, array $attrs = null) {
		return $this->getFactory()->createLabel($this->meta->createPropertyPath($forPropertyExpression), 
				$label, $attrs);
	}
	
	public function input($propertyExpression = null, array $attrs = null, string $type = null, 
			$secret = false, $fixedValue = null, $tagName = 'input', $elementContents = null) {
		$this->view->out($this->getInput($propertyExpression, $attrs, $type, $secret, 
				$fixedValue, $tagName, $elementContents));
	}

	public function getInput($propertyExpression = null, array $attrs = null, string $type = null, 
			$secret = false, $fixedValue = null, $tagName = 'input', $elementContents = null) {
		
		return $this->getFactory()->createInput($this->meta->createPropertyPath($propertyExpression), 
				$attrs, $type, $secret, $fixedValue, $tagName, $elementContents);
	}
	
	public function textarea($propertyExpression = null, array $attrs = null) {
		$this->view->out($this->getTextarea($propertyExpression, $attrs));
	}
	
	public function getTextarea($propertyExpression = null, array $attrs = null) {
		return $this->getFactory()->createTextarea($this->meta->createPropertyPath($propertyExpression), $attrs);
	}
	
	public function inputCheckbox($propertyExpression = null, $value = true, array $attrs = null, 
			$label = null, array $labelAttrs = null) {
		$this->view->out($this->getInputCheckbox($propertyExpression, $value, $attrs, $label, $labelAttrs));
	}
	
	public function getInputCheckbox($propertyExpression = null, $value = true, 
			array $attrs = null, $label = null, array $labelAttrs = null) {
		if ($label === null) {
			return $this->getFactory()->createInputCheckbox($this->meta->createPropertyPath($propertyExpression), 
					$value, $attrs);
		}
		
		$attrs = (array) $attrs;
		$labelAttrs = (array) $labelAttrs;
		if (!isset($attrs['id'])) {
			$attrs['id'] = HtmlUtils::buildUniqueId('n2n-');
		}
		$labelAttrs['for'] = $attrs['id']; 
		return $this->getFactory()->createInputCheckbox($this->meta->createPropertyPath($propertyExpression), 
						$value, $attrs, new HtmlElement('label', $labelAttrs, $label));
	}
	
	public function inputRadio($propertyExpression = null, $value = true, array $attrs = null, 
			$label = null, array $labelAttrs = null) {
		$this->view->out($this->getInputRadio($propertyExpression, $value, $attrs, $label, $labelAttrs));
	}
	
	public function getInputRadio($propertyExpression = null, $value = true, array $attrs = null, $label = null, 
			array $labelAttrs = null) {
		if ($label === null) {
			return $this->getFactory()->createInputRadio($this->meta->createPropertyPath($propertyExpression), 
					$value, $attrs, true);
		}
		
		$attrs = (array) $attrs;
		$labelAttrs = (array) $labelAttrs;
		if (!isset($attrs['id'])) {
			$attrs['id'] = HtmlUtils::buildUniqueId('n2n-');
		}
		$labelAttrs['for'] = $attrs['id']; 
		$raw = new Raw($this->getFactory()->createInputRadio($this->meta->createPropertyPath($propertyExpression), 
				$value, $attrs, false)); 
		$raw->append(new HtmlElement('label', $labelAttrs, $label));
		return $raw;
	}
	
	public function select($propertyExpression, array $options, array $attrs = null, $multiple = false) {
		$this->view->out($this->getSelect($propertyExpression, $options, $attrs, $multiple));
	}

	public function getSelect($propertyExpression, array $options, array $attrs = null, $multiple = false) {
		return $this->getFactory()->createSelect($this->meta->createPropertyPath($propertyExpression), $options, 
				$attrs, $multiple);
	}
	
	public function inputFileWithLabel($propertyExpression = null, array $attrs = null, array $labelAttrs = null) {
		$this->inputFile($propertyExpression, $attrs);
		$this->inputFileLabel($propertyExpression, $labelAttrs);
	}
	
	public function getInputFileWithLabel($propertyExpression = null, array $attrs = null, array $labelAttrs = null) {
		$raw = new Raw($this->getInputFile($propertyExpression, $attrs));
		$raw->append($this->getInputFileLabel($propertyExpression, $labelAttrs));
		return $raw;
	}
	
	public function inputFile($propertyExpression = null, array $attrs = null) {
		$this->view->out($this->getInputFile($propertyExpression, $attrs));
	}
	
	public function getInputFile($propertyExpression = null, array $attrs = null) {
		return $this->getFactory()->createInputFile($this->meta->createPropertyPath($propertyExpression), $attrs);
			
	}
	
	public function inputFileLabel($propertyExpression = null, array $attrs = null, $deleteLinkLabel = null) {
		$this->view->out($this->getInputFileLabel($propertyExpression, $attrs, $deleteLinkLabel));
	}
	
	public function getInputFileLabel($propertyExpression = null, array $attrs = null, 
			$deleteLinkLabel = null) {
		return $this->getFactory()->createInputFileLabel($this->meta->createPropertyPath($propertyExpression), 
				$attrs, $deleteLinkLabel);
	}
	
	public function inputSubmit($methodName, $value = null, array $attrs = null) {
		$this->view->out($this->getInputSubmit($methodName, $value, $attrs));
	}

	public function getInputSubmit($methodName, $value = null, array $attrs = null) {
		return $this->getFactory()->createInputSubmit($methodName, $value, $attrs);
	}

	public function buttonSubmit($methodName, $label, array $attrs = null) {
		$this->view->out($this->getButtonSubmit($methodName, $label, $attrs));
	}

	public function getButtonSubmit($methodName, $label, array $attrs = null) {	
		return $this->getFactory()->createButtonSubmit($methodName, $label, $attrs);
	}

	public function submitLink($methodName, array $attrs = null) {
		$this->view->out($this->getSubmitLink($methodName, $attrs));
	}

	public function getSubmitLink($methodName, array $attrs = null) {
		throw new NotYetImplementedException('Submit link is not yet implemented.');
// 		try {
// 			return new SubmitLink($this->meta->getForm(), $method, $attrs);
// 		} catch (UiException $e) {
// 			throw $this->view->decorateException($e);
// 		}
	}
	
// 	public function optionalObjectActivator($propertyExpression = null) {
// 		$this->view->out($this->getOptionalObjectActivator($propertyExpression));
// 	}
	
// 	public function getOptionalObjectActivator($propertyExpression = null) {
// 		$emptyAllowed = null !== $this->meta()->getForm()->getDispatchTargetEncoder()->getPseudoBasePropertyPath();
// 		return $this->getFactory()->createOptionalObjectActivator(
// 				$this->meta->createPropertyPath($propertyExpression, $emptyAllowed));
// 	}
	
	public function optionalObjectCheckbox($propertyExpression = null, array $attrs = null, 
			$label = null, array $labelAttrs = null) {
		$this->view->out($this->getOptionalObjectCheckbox($propertyExpression, $attrs, 
				$label, $labelAttrs));
	}
	
	/**
	 * @param string $propertyExpression
	 * @param array $attrs
	 * @param string $label
	 * @param array $labelAttrs
	 * @return \n2n\web\ui\UiComponent
	 */
	public function getOptionalObjectCheckbox($propertyExpression = null, array $attrs = null, 
			$label = null, array $labelAttrs = null) {
		
		$emptyAllowed = null !== $this->meta()->getForm()->getDispatchTargetEncoder()->getPseudoBasePropertyPath();
		return $this->getFactory()->createOptionalObjectCheckbox(
				$this->meta->createPropertyPath($propertyExpression, $emptyAllowed), 
				$attrs, $label, $labelAttrs);
	}
	
	public function optionalObjectEnabledHidden($propertyExpression = null) {
		$this->view->out($this->getOptionalObjectEnabledHidden($propertyExpression));
	}
	
	public function getOptionalObjectEnabledHidden($propertyExpression = null) {
		return $this->getFactory()->createEnabledOptionalObjectHidden($this->meta->createPropertyPath($propertyExpression,
				null !== $this->meta()->getForm()->getDispatchTargetEncoder()->getPseudoBasePropertyPath()));
	}
	
	public function magOpen($tagName, $propertyExpression = null, array $attrs = null, UiOutfitter $uiOutfitter = null) {
		$this->view->out($this->getMagOpen($tagName, $propertyExpression, $attrs, $uiOutfitter));
	}

	public function getMagOpen(string $tagName, $propertyExpression = null, array $attrs = null, UiOutfitter $uiOutfitter = null) {
		$propertyPath = $this->meta->createPropertyPath($propertyExpression);
		$magWrapper = $this->meta->lookupMagWrapper($propertyPath);
		$this->magStack[] = array('tagName' => $tagName, 'propertyPath' => $propertyPath, 'magWrapper' => $magWrapper,
				'outfitter' => $uiOutfitter ?? new BasicUiOutfitter());

		return new Raw('<' . HtmlUtils::hsc($tagName) . HtmlElement::buildAttrsHtml(
				HtmlUtils::mergeAttrs($magWrapper->getContainerAttrs($this->view), (array) $attrs)) . '>');
	}
	
	private function peakMagInfo() {
		if (!sizeof($this->magStack)) {
			throw new IllegalStateException('No mag opened.');
		}
		
		return end($this->magStack);
	}
	
	public function magLabel(array $attrs = null, $label = null) {
		$this->view->out($this->getMagLabel($attrs, $label));
	}
	
	public function getMagLabel(array $attrs = null, $label = null) {
		$magInfo = $this->peakMagInfo();

		if ($attrs === null) {
			$attrs = array();
		}

		//$attrs = HtmlUtils::mergeAttrs($magInfo['outfitter']->buildAttrs(UiOutfitter::NATURE_MAG_LABEL), $attrs);

		return $this->getLabel($magInfo['propertyPath'],
				($label === null ? $magInfo['magWrapper']->getMag()->getLabel($this->view->getN2nLocale()) : $label), $attrs);
	}
	
	public function magField() {
		$this->view->out($this->getMagField());
	}
	
	public function getMagField() {
		$magInfo = $this->peakMagInfo();
		return $magInfo['magWrapper']->getMag()->createUiField($magInfo['propertyPath'], $this->view,
				$magInfo['outfitter']);
	}

	public function magClose() {
		$this->view->out($this->getMagClose());
	}
	
	public function getMagClose() {
		$optionInfo = $this->peakMagInfo();
		array_pop($this->magStack);
		return new Raw('</' . HtmlUtils::hsc($optionInfo['tagName']) . '>');
	}
}
