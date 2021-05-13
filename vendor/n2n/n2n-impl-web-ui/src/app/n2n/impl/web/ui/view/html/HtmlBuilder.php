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
namespace n2n\impl\web\ui\view\html;

use n2n\core\N2N;
use n2n\impl\web\ui\view\html\img\ImgComposer;
use n2n\impl\web\ui\view\html\img\UiComponentFactory;
use n2n\io\managed\File;
use n2n\io\managed\img\ThumbStrategy;
use n2n\io\ob\OutputBuffer;
use n2n\util\type\ArgUtils;
use n2n\web\ui\CouldNotRenderUiComponentException;
use n2n\web\ui\Raw;
use n2n\web\ui\UiComponent;
use n2n\web\ui\UiException;
use n2n\io\managed\impl\AssetFileManager;
use n2n\util\type\CastUtils;
use n2n\io\managed\impl\engine\QualifiedNameBuilder;
use n2n\util\uri\Path;
use n2n\core\VarStore;

class HtmlBuilder {
	private $view;
	private $meta;
	private $contentBuffer;
	private $request;

	/**
	 * @param HtmlView $view
	 * @param OutputBuffer $contentBuffer
	 */
	public function __construct(HtmlView $view, OutputBuffer $contentBuffer) {
		$this->view = $view;
		$this->meta = new HtmlBuilderMeta($view);
		$this->contentBuffer = $contentBuffer;
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HtmlBuilderMeta
	 */
	public function meta() {
		return $this->meta;
	}

	/**
	 * @param array $attrs
	 */
	public function headStart(array $attrs = null) {
		$this->view->out('<head' . HtmlElement::buildAttrsHtml($attrs) . '>' . "\r\n");
	}

	/**
	 * 
	 */
	public function headContents() {
		$htmlProperties = $this->meta->getHtmlProperties();
		
		if (!$htmlProperties->containsName(HtmlBuilderMeta::HEAD_TITLE_KEY)) {
			$htmlProperties->set(HtmlBuilderMeta::HEAD_TITLE_KEY, new HtmlElement('title', null,
					N2N::getAppConfig()->general()->getPageName()));
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_TITLE_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_TITLE_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_META_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_META_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_LINK_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_LINK_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_SCRIPT_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_SCRIPT_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_CONTENTS_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_CONTENTS_KEY);
		}
	}

	/**
	 * 
	 */
	public function headEnd() {
		$this->headContents();

		$this->view->out('</head>' . "\r\n");
	}

	/**
	 * @param array $attrs
	 */
	public function bodyStart(array $attrs = null) {
		$this->view->out('<body' . HtmlElement::buildAttrsHtml($attrs) . '>' . "\r\n");
		$this->bodyStartContents();
	}

	/**
	 * 
	 */
	public function bodyStartContents() {
		$this->contentBuffer->breakPoint(HtmlBuilderMeta::TARGET_BODY_START);
	}

	/**
	 * 
	 */
	public function bodyEndContents() {
		$this->contentBuffer->breakPoint(HtmlBuilderMeta::TARGET_BODY_END);
	}

	/**
	 * 
	 */
	public function bodyEnd() {
		$this->bodyEndContents();
		$this->view->out('</body>' . "\r\n");
	}

	/*
	 * BASIC UTILS
	 */

	/**
	 * @param mixed $contents
	 */
	public function out($contents) {
		$this->view->out($this->getOut($contents));
	}
	
	public function getOut($contents) {
		if ($contents instanceof UiComponent) {
			return $contents;
		}
		
		return $this->getEsc($contents);
	}
	
// 	public function getUic($arg) {
// 		if ($arg instanceof UiComponent) {
// 			return $arg;
// 		}
		
// 		return new Raw($arg);
// 	}
	
	/**
	 * @param mixed $contents
	 */
	public function esc($contents) {
		$this->view->out($this->getEsc($contents));
	}
	
	/**
	 * @param mixed $contents
	 * @return \n2n\web\ui\Raw
	 */
	public function getEsc($contents) {
		return new Raw(HtmlUtils::escape($contents));
	}
	/**
	 * @param mixed $contents
	 * @param array $attrs
	 * @param string $strict
	 */
	public function escP($contents, array $attrs = null, bool $strict = false) {
		$this->view->out($this->getEscP($contents, $attrs, $strict));
	}
	/**
	 * @param mixed $contents
	 * @param string $strict
	 * @return \n2n\web\ui\Raw
	 */
	public function getEscP($contents, array $attrs = null, bool $strict = false): UiComponent {
	    $attrsHtml = HtmlElement::buildAttrsHtml($attrs);
		$html = HtmlUtils::escape($contents, function ($html) use ($strict, $attrsHtml) {
			$html = str_replace("\n\n", '</p><p' . $attrsHtml . '>', str_replace("\r", '', $html));
			if (!$strict) $html = nl2br($html);
			return $html;
		});
		
		return new Raw('<p' . $attrsHtml . '>' . $html . '</p>');
	}
	/**
	 * @param string $contents
	 */
	public function escBr($contents) {
		$this->view->out($this->getEscBr($contents));
	}
	/**
	 * @param string $string
	 * @return \n2n\web\ui\Raw
	 */
	public function getEscBr($contents) {
		$html = HtmlUtils::escape($contents, function ($html) {
			return nl2br($html);
		});
		
		return new Raw($html);
	}
	
	/*
	 * NAVIGATION UTILS
	 */

	/**
	 * @param mixed $murl
	 * @param mixed $label
	 * @param array $attrs
	 */
	public function link($murl, $label = null, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		$this->view->out($this->getLink($murl, $label, $attrs, $alternateTagName, $alternateAttrs, $required));
	}
	
	/**
	 * @param string $murl
	 * @param mixed $label
	 * @param array $attrs
	 * @throws \n2n\util\uri\UnavailableUrlException
	 * @return \n2n\impl\web\ui\view\html\Link
	 */
	public function getLink($murl, $label = null, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		if ($label === null) {
			$suggestedLabel = null;
			$murl = $this->view->buildUrlStr($murl, $required, $suggestedLabel);
			$label = $this->meta->buildLinkLabel($murl, $suggestedLabel);
		}
		
		$raw = new HtmlSnippet();
		$raw->append($this->getLinkStart($murl, $attrs, $alternateTagName, $alternateAttrs, $required));
		$raw->append($label);
		$raw->append($this->getLinkEnd());
		
		return $raw;
	}
	
	private $linkStartedData = null;
	
	private function ensureLinkStarted() {
		if ($this->linkStartedData === null) {
			throw new UiException('No Link started.');
		}
	}
	
	public function linkStart($murl, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		$this->view->out($this->getLinkStart($murl, $attrs, $alternateTagName, $alternateAttrs, $required));
	}
	
	/**
	 * @param array $attrs
	 * @return array
	 */
	private function secureLinkAttrs(array $attrs) {
		if (isset($attrs['target']) && $attrs['target'] == '_blank' && !array_key_exists('rel', $attrs)) {
			$attrs['rel'] = 'noopener';
		}
		return $attrs;
	}
	
	public function getLinkStart($murl, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		if ($this->linkStartedData !== null) {
			throw new UiException('Link already started.');
		}
		
		$href = null;
		$suggestedLabel = null;
		if ($murl !== null) {
			$href = $this->view->buildUrlStr($murl, $required, $suggestedLabel);
		}
		
		$attrs = $this->secureLinkAttrs((array) $attrs);
		
		if ($href !== null) {
			$this->linkStartedData = array('tagNameHtml' => 'a', 'href' =>  $href, 'suggestedLabel' => $suggestedLabel);
			$attrs = HtmlUtils::mergeAttrs(array('href' =>  $href), $attrs);
			return new Raw('<a ' . HtmlElement::buildAttrsHtml($attrs) . '>');
		} 
		
		if ($alternateTagName !== null) {
			$this->linkStartedData = array('tagNameHtml' => $this->getEsc($alternateTagName));
		} else {
			$this->linkStartedData = array('tagNameHtml' => 'a');
		}
		
		return new Raw('<' . $this->linkStartedData['tagNameHtml'] . ' ' 
				. HtmlElement::buildAttrsHtml($alternateAttrs ?? $attrs) . '>');
	}
	
	public function linkLabel($label = null) {
		$this->view->out($this->getLinkLabel($label));
	}
	
	public function getLinkLabel($label = null) {
		$this->ensureLinkStarted();
		
		if ($label !== null) {
			return $this->getOut($label);
		}
		
		if (isset($this->linkStartedData['suggestedLabel'])) {
			return $this->getOut($this->linkStartedData['suggestedLabel']);
		}
		
		if (isset($this->linkStartedData['href'])) { 
			return $this->getOut($this->meta->buildLinkLabel($this->linkStartedData['href']));
		}
		
		return null;
	}
	
	public function linkEnd() {
		$this->view->out($this->getLinkEnd());
	}
	
	public function getLinkEnd() {
		$this->ensureLinkStarted();
		
		$raw = new Raw('</' . $this->linkStartedData['tagNameHtml'] . '>');
		$this->linkStartedData = null;
		return $raw;
	}

	/**
	 * @param mixed $pathExt
	 * @param string|UiComponent $label
	 * @param array $attrs
	 * @param array $query
	 * @param string $fragment
	 * @param string $ssl
	 * @param string $subsystem
	 */
	public function linkToContext($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToContext($pathExt, $label, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	/**
	 * @param mixed $pathExt
	 * @param mixed $label
	 * @param array $attrs
	 * @param array $query
	 * @param string $fragment
	 * @param bool $ssl
	 * @param string $subsystem
	 * @return \n2n\impl\web\ui\view\html\Link
	 */
	public function getLinkToContext($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getContextUrl($pathExt, $query, $fragment, $ssl, $subsystem), 
				$label, $attrs);
	}

	public function linkToController($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $contextKey = null, $ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToController($pathExt, $label, $attrs, $query, $fragment, 
				$contextKey, $ssl, $subsystem));
	}
	
	public function getLinkToController($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$contextKey = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getControllerUrl($pathExt, $query, $fragment, $contextKey, $ssl, $subsystem), 
				$label, $attrs);
	}

	public function linkToPath($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToPath($pathExt, $label, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	public function getLinkToPath($pathExt = null, $label = null, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getPath($pathExt, $query, $fragment, $ssl, $subsystem), $label, $attrs);
	}

	public function linkToUrl($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToUrl($pathExt, $label, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	public function getLinkToUrl($pathExt = null, $label = null, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getUrl($pathExt, $query, $fragment, $ssl, $subsystem), $label, $attrs);
	}
	
	/**
	 * 
	 * @param string $email
	 * @param string|UiComponent $label
	 * @param array $attrs
	 */
	public function linkEmail(string $email, $label = null, array $attrs = null) {
		$this->view->out($this->getLinkEmail($email, $label, $attrs));
	}
	
	/** 
	 * @param string $email
	 * @param string|UiComponent $label
	 * @param array $attrs
	 * @return \n2n\web\ui\Raw
	 */
	public function getLinkEmail(string $email, $label = null, array $attrs = null) {
		$uriHtml = HtmlUtils::encodedEmailUrl($email);
		HtmlUtils::validateCustomAttrs((array) $attrs, array('href'));
		return new HtmlSnippet(
				new Raw('<a href="' . $uriHtml . '"' . HtmlElement::buildAttrsHtml($attrs) . '>'),
				($label !== null ? $label : new Raw(HtmlUtils::encode($email))),
				new Raw('</a>'));
	}
	/**
	 * 
	 * @param string $email
	 * @param string|UiComponent $label
	 * @param array $attrs
	 */
	public function linkTel(string $tel, $label = null, array $attrs = null) {
		$this->view->out($this->getLinkTel($tel, $label, $attrs));
	}
	
	/** 
	 * @param string $email
	 * @param string|UiComponent $label
	 * @param array $attrs
	 * @return \n2n\web\ui\Raw
	 */
	public function getLinkTel(string $tel, $label = null, array $attrs = null) {
		HtmlUtils::validateCustomAttrs((array) $attrs, array('href'));

		return new HtmlSnippet(
				new Raw('<a href="tel:' . preg_replace('/[^0-9\\+]/', '', str_replace('(0)', '', $tel)) . '"' 
						. HtmlElement::buildAttrsHtml($attrs) . '>'),
				($label !== null ? $label : new Raw($tel)),
				new Raw('</a>'));
	}
	
	/*
	 * MESSAGE CONTAINER UTILS 
	 */
	
	/**
	 * @param string $groupName
	 * @param int $severity
	 * @param array $attrs
	 * @param array $errorAttrs
	 * @param array $warnAttrs
	 * @param array $infoAttrs
	 * @param array $successAttrs
	 */
	public function messageList(string $groupName = null, int $severity = null, array $attrs = null, array $errorAttrs = null, 
			array $warnAttrs = null, array $infoAttrs = null, array $successAttrs = null) {
		$this->view->out($this->getMessageList($groupName, $severity, $attrs, $errorAttrs, 
				$warnAttrs, $infoAttrs, $successAttrs));
	}
	
	/**
	 * @param string $groupName
	 * @param int $severity
	 * @param array $attrs
	 * @param array $errorAttrs
	 * @param array $warnAttrs
	 * @param array $infoAttrs
	 * @param array $successAttrs
	 * @return \n2n\impl\web\ui\view\html\MessageList
	 */
	public function getMessageList(string $groupName = null, int $severity = null, array $attrs = null, array $errorAttrs = null, 
			array $warnAttrs = null, array $infoAttrs = null, array $successAttrs = null) {
		
		return new MessageList($this->view->getDynamicTextCollection(), 
				$this->meta->getMessages($groupName, $severity), $attrs, 
				$errorAttrs, $warnAttrs, $infoAttrs, $successAttrs);
	}
	
	/*
	 * L10N UTILS 
	 */
	
	/**
	 * @param string $code
	 * @param array $args
	 * @param string $num
	 * @param array $replacements
	 * @param string|\n2n\core\module\Module $module
	 */
	public function text(string $code, array $args = null, int $num = null, array $replacements = null, 
			$module = null) {
		$this->l10nText($code, $args, $num, $replacements, $module);
	}
	
	/**
	 * @param string $code
	 * @param array $args
	 * @param int $num
	 * @param int $replacements
	 * @param string|\n2n\core\module\Module $module
	 * @return \n2n\web\ui\Raw
	 */
	public function getText(string $code, array $args = null, int $num = null, array $replacements = null, 
			$module = null) {
		return $this->getL10nText($code, $args, $num, $replacements, $module);
	}
		
	/**
	 * @param string $key
	 * @param array $args
	 * @param int $num
	 * @param array $replacements
	 * @param string|\n2n\core\module\Module $module
	 * @return \n2n\web\ui\Raw
	 */
	public function getL10nText(string $key, array $args = null, int $num = null, array $replacements = null, $module = null) {
		$textRaw = $this->getEsc($this->view->getL10nText($key, $args, $num, null, $module));
		if (empty($replacements)) return $textRaw;
		
// 		$textHtml = (string) $textRaw;
// 		foreach ($replacements as $key => $replacement) {
// 			$textHtml = str_replace(DynamicTextCollection::REPLACEMENT_PREFIX . $key . DynamicTextCollection::REPLACEMENT_SUFFIX, 
// 					HtmlUtils::contentsToHtml($replacement, $this->view->getContentsBuildContext()), $textHtml);
// 		}

		$textHtml = HtmlBuilderMeta::replace((string) $textRaw, $replacements, $this->view);
		return new Raw($textHtml);
	}
	
	/**
	 * @param string $key
	 * @param array $args
	 * @param int $num
	 * @param array $replacements
	 * @param string|\n2n\core\module\Module $module
	 * @return \n2n\web\ui\Raw
	 */
	public function l10nText(string $key, array $args = null, int $num = null, array $replacements = null, $module = null) {
		$this->view->out($this->getL10nText($key, $args, $num, $replacements, $module));
	}
	
	public function getL10nNumber($value, $style = \NumberFormatter::DECIMAL, $pattern = null) {
		return $this->getEsc($this->view->getL10nNumber($value, $style, $pattern));
	}
	
	public function l10nNumber($value, $style = \NumberFormatter::DECIMAL, $pattern = null) {
		$this->view->out($this->getL10nNumber($value, $style, $pattern));
	}
	
	/**
	 * @see self::getL10nCurrency()
	 * @return \n2n\web\ui\Raw
	 */
	public function getL10nCurrency($value, $currency = null) {
		return $this->getEsc($this->view->getL10nCurrency($value, $currency));
	}
	
	/**
	 * @param float $value
	 * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
	 */
	public function l10nCurrency($value, $currency = null) {
		$this->view->out($this->getL10nCurrency($value, $currency));
	}
	
	public function getL10nDate($value, $dateStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDate($value, $dateStyle, $timeZone));
	}
	
	public function l10nDate($value, $dateStyle = null, \DateTimeZone $timeZone = null) {
		return $this->view->out($this->getL10nDate($value, $dateStyle, $timeZone));
	}
	
	public function getL10nTime($value, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nTime($value, $timeStyle, $timeZone));
	}
	
	public function l10nTime($value, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->view->out($this->getL10nTime($value, $timeStyle, $timeZone));
	}
	
	public function getL10nDateTime($value, $dateStyle = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDateTime($value, $dateStyle, $timeStyle, $timeZone));
	}
	
	public function l10nDateTime(\DateTime $value = null, $dateStyle = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		$this->view->out($this->getL10nDateTime($value, $dateStyle, $timeStyle, $timeZone));
	}
	
	public function getL10nDateTimeFormat(\DateTime $dateTime = null, $icuPattern, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDateTimeFormat($dateTime, $icuPattern, $timeZone));
	}
	
	public function l10nDateTimeFormat(\DateTime $dateTime = null, $icuPattern, \DateTimeZone $timeZone = null) {
		$this->view->out($this->getL10nDateTimeFormat($dateTime, $icuPattern, $timeZone));
	}
	
	/*
	 * IMAGE UTILS
	 */
	
	public function image(File $file = null, $imgComposer = null, array $attrs = null, 
			bool $attrWidth = true, bool $attrHeight = true) {
		$this->view->out($this->getImage($file, $imgComposer, $attrs, $attrWidth, $attrHeight));
	}
	
	
	
	public function getImage(File $file = null, $imgComposer = null, array $attrs = null, 
			bool $addWidthAttr = true, bool $addHeightAttr = true) {
		ArgUtils::valType($imgComposer, array(ImgComposer::class, ThumbStrategy::class), true);
		
		if ($imgComposer instanceof ImgComposer) {
			$imgSet = $imgComposer->createImgSet($file, $this->view->getN2nContext());
			
			if (!$imgSet->isPictureRequired()) {
				return UiComponentFactory::createImg($imgSet, $attrs, $addWidthAttr, $addHeightAttr);
			} else {
				$alt = null;
				if (isset($attrs['alt'])) {
					$alt = $attrs['alt'];
					unset($attrs['alt']);
				}
				return UiComponentFactory::createPicture($imgSet, $attrs, $alt);
			}
		}
		
		return $this->getImg($file, $imgComposer, $attrs, $addWidthAttr, $addHeightAttr);
	}
	
	public function getImg(File $file = null, $imgComposer = null, array $attrs = null, 
			bool $addWidthAttr = true, bool $addHeightAttr = true) {
		ArgUtils::valType($imgComposer, array(ImgComposer::class, ThumbStrategy::class), true);
		
		if ($imgComposer instanceof ImgComposer) {
			$imgSet = $imgComposer->createImgSet($file);
				
			if ($imgSet->isPictureRequired()) {
				throw new CouldNotRenderUiComponentException('ImgComposer requires picture element.');
			} 
			
			return UiComponentFactory::createImg($imgSet, $attrs, $addWidthAttr, $addHeightAttr);
		}
		
		return UiComponentFactory::createImgFromThSt($file, $imgComposer, $attrs, $addWidthAttr, $addHeightAttr);
	}
	
	public function getPicture(File $file = null, ImgComposer $imgComposer = null, array $attrs = null) {
		if ($imgComposer === null) {
			return UiComponentFactory::createPicture($imgComposer, $attrs);
		}
				
		return UiComponentFactory::createPicture($imgComposer->createImgSet($file, $this->view->getN2nContext()), 
				$attrs);
	}
	
	
	public function imageAsset($pathExt, $alt, array $attrs = null, string $moduleNamespace = null) {
		$this->view->out($this->getImageAsset($pathExt, $alt, $attrs, $moduleNamespace));
	}
	
	public function getImageAsset($pathExt, $alt, array $attrs = null, string $moduleNamespace = null) {
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		return new HtmlElement('img', HtmlUtils::mergeAttrs(
				array('src' => $this->view->getHttpContext()->getAssetsUrl($moduleNamespace)->ext($pathExt), 
						'alt' => $alt), (array) $attrs));
	}
	
	/**
	 * @param mixed $pathExt
	 * @param mixed $imgComposer
	 * @param array $attrs
	 * @param string $moduleNamespace
	 * @param bool $addWidthAttr
	 * @param bool $addHeightAttr
	 */
	public function imageMimgAsset($pathExt, $imgComposer = null, array $attrs = null, string $moduleNamespace = null,
			bool $addWidthAttr = true, bool $addHeightAttr = true) {
		$this->out($this->getImageMimgAsset($pathExt, $imgComposer, $attrs, $moduleNamespace, $addWidthAttr, $addHeightAttr));
	}
	
	/**
	 * @param mixed $pathExt
	 * @param mixed $imgComposer
	 * @param array $attrs
	 * @param string $moduleNamespace
	 * @param bool $addWidthAttr
	 * @param bool $addHeightAttr
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	public function getImageMimgAsset($pathExt, $imgComposer = null, array $attrs = null, string $moduleNamespace = null,
			bool $addWidthAttr = true, bool $addHeightAttr = true) {
		$assetFileManager = $this->view->lookup(AssetFileManager::class);
		CastUtils::assertTrue($assetFileManager instanceof AssetFileManager);
		
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		
		$qnLevels = [VarStore::namespaceToDirName($moduleNamespace)];
		array_push($qnLevels, ...Path::create($pathExt)->getPathParts());
		$fileName = array_pop($qnLevels);
		
		$file = $assetFileManager->getByQualifiedName((new QualifiedNameBuilder($qnLevels, $fileName))->__toString());
		
		return $this->getImage($file, $imgComposer, $attrs, $addWidthAttr, $addHeightAttr);
	}
}