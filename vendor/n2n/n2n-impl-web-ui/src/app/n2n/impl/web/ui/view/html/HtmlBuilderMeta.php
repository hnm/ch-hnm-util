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

use n2n\util\type\ArgUtils;
use n2n\web\ui\Raw;
use n2n\util\uri\Url;
use n2n\web\http\nav\Murl;
use n2n\l10n\DynamicTextCollection;
use n2n\l10n\MessageContainer;
use n2n\core\config\GeneralConfig;
use n2n\web\http\ServerPushDirective;
use n2n\web\ui\UiComponent;
use n2n\web\ui\SimpleBuildContext;

/**
 * Accessible through <code>$html-&#x3E;meta()</code> in every html view
 */
class HtmlBuilderMeta {
	private $view;
	private $htmlProperties;
	
	/**
	 * @param HtmlView $view
	 */
	public function __construct(HtmlView $view) {
		$this->view = $view;
		$this->htmlProperties = $view->getHtmlProperties();
	}
	
	/**
	 * @return HtmlView
	 */
	public function getView() {
		return $this->view;
	}
	
	/**
	 * @return HtmlProperties
	 */
	public function getHtmlProperties() {
		return $this->htmlProperties;
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HeadBuilderMeta
	 */
	public function head() {
		return new HeadBuilderMeta($this, self::TARGET_HEAD);
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HeadBuilderMeta
	 */
	public function bodyStart() {
		return new HeadBuilderMeta($this, self::TARGET_BODY_START);
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HeadBuilderMeta
	 */
	public function bodyEnd() {
		return new HeadBuilderMeta($this, self::TARGET_BODY_END);
	}
	
	/*
	 * HTML HEAD UTILS
	 */
	
	/**
	 * @param string $title if null is passed nothing will happen. Due to complaints of Thomas Jansen and Thomas Günther.
	 * @param bool $includePageName
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function setTitle(?string $title, bool $includePageName = false) {
		if ($title === null) {
			return $this;
		}
		
		$this->htmlProperties->set(self::HEAD_TITLE_KEY, new HtmlElement('title', null, $title
				. ($includePageName ?  ' - ' . $this->getPageName() : '')));
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getPageName() {
		return $this->view->getN2nContext()->lookup(GeneralConfig::class)->getPageName();
	}
	
	/**
	 * @param Url|string $relativeUrl
	 * @param string $media
	 * @param string $moduleNamespace
	 * @param boolean $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addCss($relativeUrl, string $media = null, string $moduleNamespace = null, bool $prepend = false,
			array $attrs = null, $target = self::TARGET_HEAD) {
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		
		$this->addCssUrl($this->view->getHttpContext()->getAssetsUrl($moduleNamespace)->ext($relativeUrl),
				$media, $prepend, $attrs, $target);
		
		return $this;
	}
	
	/**
	 * @param string $href
	 * @param string|null $media
	 * @param boolean $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addCssUrl(string $href, string $media = null, bool $prepend = false, array $attrs = null,
			$target = self::TARGET_HEAD) {
		if ($target == self::TARGET_HEAD) {
			$target = self::HEAD_SCRIPT_KEY;
		}
		
		$this->htmlProperties->add($target, 'rel:stylesheet:' . (string) $href . ':' . (string) $media,
				new HtmlElement('link', array('rel' => 'stylesheet', 'type' => 'text/css', 'media' => $media, 'href' => $href)),
				$prepend);
		
		return $this;
	}
	
	/**
	 * @param Url|string $relativeUrl
	 * @param string $moduleNamespace
	 * @param boolean $prepend
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addAsyncJs($relativeUrl, string $moduleNamespace = null, $prepend = false) {
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		
		$this->addAsyncJsUrl($this->view->getHttpContext()->getAssetsUrl($moduleNamespace)->ext($relativeUrl), (boolean) $prepend);
		
		return $this;
	}
	
	/**
	 * @param string $src
	 * @param bool $prepend
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addAsyncJsUrl(string $src, bool $prepend = false) {
		$this->htmlProperties->add(self::HEAD_SCRIPT_KEY, 'type:javascript:src:' . $src,
				new HtmlElement('script', array(/*'type' => 'text/javascript'*/),
						"\r\n" . '//<![CDATA[' . "\r\n" . '(function() {var b=document.createElement("script");b.type="text/javascript";b.async=true;b.src="' .
						addslashes($src) . '";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(b,a)})();' . "\r\n" . '//]]>' . "\r\n"),
				$prepend);
		
		return $this;
	}
	
	/**
	 * @param Url|string $relativeUrl
	 * @param string $moduleNamespace
	 * @param bool $defer
	 * @param bool $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addJs($relativeUrl, string $moduleNamespace = null, bool $defer = false, bool $prepend = false,
			array $attrs = null, $target = self::TARGET_HEAD) {
		if (null === $moduleNamespace) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
				
		$this->addJsUrl($this->view->getHttpContext()->getAssetsUrl($moduleNamespace)->ext($relativeUrl), $defer,
				$prepend, $attrs, $target);
		
		return $this;
	}
	
	/**
	 * @param string $src
	 * @param bool $defer
	 * @param bool $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addJsUrl(string $src, bool $defer = false, bool $prepend = false, array $attrs = null, 
			$target = self::TARGET_HEAD) {
		ArgUtils::valEnum($target, array(self::TARGET_HEAD, self::TARGET_BODY_START, self::TARGET_BODY_END));
		
		if ($target == self::TARGET_HEAD) {
			$target = self::HEAD_SCRIPT_KEY;
		}
		
		$attrs = (array) $attrs;
		if ($defer) {
			$attrs['defer'] = 'defer';
		}
		
		$htmlElement = new HtmlElement('script', HtmlUtils::mergeAttrs(
				array(/*'type' => 'text/javascript', */'src' => $src), $attrs), '');
		
		$this->htmlProperties->add($target, 'type:javascript:src:' . $src, $htmlElement, $prepend);
		
		return $this;
	}
	
	/**
	 * @param array $attrs
	 * @param string $uniqueAttrName
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	private function buildKey(array $attrs, string $uniqueAttrName = null) {
		if ($uniqueAttrName === null) {
			return 'type:attrs:' . serialize($attrs);
		}
		
		if (!array_key_exists($uniqueAttrName, $attrs)) {
			throw new \InvalidArgumentException('Passed unique attrs name does not exist. Available attr names: '
					. implode(', ', array_keys($attrs)));
		}
		
		return 'type:key:' . $uniqueAttrName . ':' . (string) $attrs[$uniqueAttrName];
		
	}
	
	/**
	 * @param string|null $description if null is passed nothing will happen. Due to complaints of Thomas Jansen and Thomas Günther.
	 * @return \n2n\impl\web\ui\view\html\HtmlBuilderMeta
	 */
	public function setMetaDescription(?string $description) {
		if ($description === null) {
			return $this;
		}
		
		$this->addMeta(['name' => 'description', 'content' => $description], 'name');

		return $this;
	}
	
	/**
	 * @param array $attrs
	 * @param string $uniqueAttrName
	 * @param bool $prepend
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addMeta(array $attrs, string $uniqueAttrName = null, bool $prepend = false) {
		$this->htmlProperties->add(self::HEAD_META_KEY, $this->buildKey($attrs, $uniqueAttrName),
				new HtmlElement('meta', $attrs), $prepend);
		
		return $this;
	}
	
	/**
	 * @param array $attrs
	 * @param string $uniqueAttrName
	 * @param bool $prepend
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addLink(array $attrs, string $uniqueAttrName = null, bool $prepend = false) {
		$this->htmlProperties->add(self::HEAD_LINK_KEY, $this->buildKey($attrs, $uniqueAttrName),
				new HtmlElement('link', $attrs), $prepend);
		
		return $this;
	}
	
	const HEAD_TITLE_KEY = 'head.title';
	const HEAD_SCRIPT_KEY = 'head.script';
	const HEAD_LINK_KEY = 'head.link';
	const HEAD_META_KEY = 'head.meta';
	const HEAD_CONTENTS_KEY = 'head.contents';
	
	/**
	 * @return string[]
	 */
	public static function getHeadKeys() {
		return array(self::HEAD_TITLE_KEY, self::HEAD_SCRIPT_KEY, self::HEAD_LINK_KEY, self::HEAD_META_KEY, 
				self::HEAD_CONTENTS_KEY);
	}
	
	const TARGET_HEAD = 'head';
	const TARGET_BODY_START = 'body.start';
	const TARGET_BODY_END = 'body.end';
	
	/**
	 * @return string[]
	 */
	public static function getTargets() {
		return array(self::TARGET_HEAD, self::TARGET_BODY_START, self::TARGET_BODY_END);
	}
	
	/**
	 * @return string[]
	 */
	public static function getKeys() {
		return array(self::HEAD_TITLE_KEY, self::HEAD_SCRIPT_KEY, self::HEAD_LINK_KEY, self::HEAD_META_KEY,
				self::HEAD_CONTENTS_KEY, self::TARGET_HEAD, self::TARGET_BODY_START, self::TARGET_BODY_END);
	}
	
	/**
	 * @param string $code
	 * @param bool $defer
	 * @param boolean $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addJsCode(string $code, bool $defer = false, $prepend = false, array $attrs = null, $target = self::TARGET_HEAD) {
		ArgUtils::valEnum($target, array(self::TARGET_HEAD, self::TARGET_BODY_START, self::TARGET_BODY_END));
		
		if ($target == self::TARGET_HEAD) {
			$target = self::HEAD_SCRIPT_KEY;
		}
		
		$attrs = array(/*'type' => 'text/javascript'*/);
		if ($defer) {
			$attrs['defer'] = 'defer';
		}
		
		$this->htmlProperties->push($target,
				new HtmlElement('script', $attrs, new Raw("\r\n" . $code . "\r\n" . "\r\n")),
				$prepend);
		
		return $this;
	}
	
	/**
	 * @param string $code
	 * @param boolean $prepend
	 * @param array $attrs
	 * @param string $target Any of {self::getTargets()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addCssCode(string $code, bool $prepend = false, array $attrs = null, $target = self::TARGET_HEAD) {
		ArgUtils::valEnum($target, self::getTargets());
		
		if ($target == self::TARGET_HEAD) {
			$target = self::HEAD_LINK_KEY;
		}
		
		$this->htmlProperties->push($target,
				new HtmlElement('style', $attrs, new Raw("\r\n" . $code . "\r\n" . "\r\n")),
				$prepend);
		
		return $this;
	}
	
	/**
	 * @param string|UiComponent $contents
	 * @param bool $prepend
	 * @param string $target
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addContents($contents, bool $prepend = false, $target = self::TARGET_BODY_START) {
		ArgUtils::valEnum($target, array(self::TARGET_BODY_START, self::TARGET_BODY_END, self::TARGET_HEAD));
		
		if ($target == self::TARGET_HEAD) {
			$target = self::HEAD_CONTENTS_KEY;
		}
		
		$this->htmlProperties->push($target, new Raw($this->view->getHtmlBuilder()->getOut($contents)), $prepend);
		
		return $this;
	}
	
	/**
	 * @param Library $library
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function addLibrary(Library $library) {
		if (!$this->view->getHtmlProperties()->registerLibrary($library)) {
			return $this;
		}
		
		$library->apply($this->view, $this);
		
		return $this;
	}
		
	/**
	 * Will create a {@see ServerPushDirective} pass it automatically to {@see Response::serverPush()} when this view 
	 * will be send.
	 *  
	 * @param Url|string $url;
	 * @param string $as Any of {@see ServerPushDirective::getAses()}
	 * 
	 * @return HtmlBuilderMeta
	 */
	public function serverPush($url, string $as) {
		$this->view->getHtmlProperties()->addServerPushDirective(new ServerPushDirective($url, $as));
		
		return $this;
	}
	
	public function getMessages($groupName = null, $severity = null) {
		$n2nContext = $this->view->getN2nContext();
		$messages = $n2nContext->lookup(MessageContainer::class)->getAll($groupName, $severity);
		
		return $messages;
	}
	
	public function getContextUrl($pathExt, $query = null, $fragment = null, $ssl = null, $subsystem = null) {
		return $this->view->buildUrl(Murl::context()->pathExt($pathExt)->queryExt($query)->fragment($fragment)->ssl($ssl)
				->subsystem($subsystem));
		// 		return $this->view->getHttpContext()->completeUrl($this->view->getHttpContext()->getRequest()->getContextPath()->ext($pathExt)
		// 						->toUrl($query, $fragment),
		// 				$ssl, $subsystem);
	}
	
	public function getControllerUrl($pathExt, $query = null, $fragment = null,
			$controllerContext = null, $ssl = null, $subsystem = null) {
		return $this->view->buildUrl(Murl::controller($controllerContext)->pathExt($pathExt)->queryExt($query)
				->fragment($fragment)->ssl($ssl)->subsystem($subsystem));
	}
	
	public function getAssetUrl($urlExt, string $moduleNamespace = null, bool $absolute = false) {
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		return $this->view->getHttpContext()->getAssetsUrl($moduleNamespace, $absolute)->ext($urlExt);
	}
	
	// 	/**
	// 	 * @param UrlComposer $murlBuilder
	// 	 * @return \n2n\impl\web\ui\view\html\Url
	// 	 */
	// 	public function buildUrl(UrlComposer $murlBuilder) {
	// 		return $murlBuilder->toUrl($this->view->getHttpContext(), $this->view->getControllerContext());
	// 	}
	
	/**
	 * @param string $pathExt
	 * @param array $query
	 * @param string $fragment
	 * @param string $ssl
	 * @param string $subsystem
	 * @return Url
	 */
	public function getPath($pathExt = null, $query = null, $fragment = null,
			$ssl = null, $subsystem = null) {
		return $this->view->getHttpContext()->buildContextUrl($ssl, $subsystem)
				->extR($this->view->getRequest()->getCmdPath()->ext($pathExt), $query, $fragment);
	}
	
	/**
	 * @param string $pathExt
	 * @param array $query
	 * @param string $fragment
	 * @param string $ssl
	 * @param string $subsystem
	 * @return Url
	 */
	public function getUrl($pathExt = null, $query = null, $fragment = null,
			$ssl = null, $subsystem = null) {
				$request = $this->view->getRequest();
		return $this->view->getHttpContext()->buildContextUrl($ssl, $subsystem)
				->extR($request->getCmdPath(), $request->getQuery())
				->extR($pathExt, $query, $fragment);
	}
	
	public function buildLinkLabel($url, string $label = null) {
		if (null !== $label) return $label;
		
		$url = Url::create($url);
		if (null !== ($hostName = $url->getAuthority()->getHost())) {
			return $hostName;
		}
		
		return str_replace(array('http://', 'https://', 'mailto:', 'tel:'), '', $url);
	}
	
	/**
	 * @param string $textHtml
	 * @param array $replacements
	 * @param HtmlView $view
	 * @return string
	 */
	public static function replace(string $textHtml, array $replacements = null, HtmlView $view = null) {
		if (empty($replacements)) return $textHtml;
		
		$buildContext = $view !== null ? $view->getContentsBuildContext() : new SimpleBuildContext();
		
		foreach ((array) $replacements as $key => $replacement) {
			$textHtml = str_replace(DynamicTextCollection::REPLACEMENT_PREFIX . $key . DynamicTextCollection::REPLACEMENT_SUFFIX,
					HtmlUtils::contentsToHtml($replacement, $buildContext), $textHtml);
		}
		
		return $textHtml;
	}
}

class HeadBuilderMeta extends BodyBuilderMeta {
	
	/**
	 * @param string $title
	 * @param bool $includePageName
	 */
	public function setTitle(string $title, bool $includePageName = false) {
		$this->meta->setTitle($title, $includePageName);
	}
	
	/**
	 * @param array $attrs
	 * @param string $uniqueAttrName
	 * @param bool $prepend
	 */
	public function addMeta(array $attrs, string $uniqueAttrName = null, bool $prepend = false) {
		$this->meta->addMeta($attrs, $uniqueAttrName, $prepend);
	}
	
	/**
	 * @param array $attrs
	 * @param string $uniqueAttrName
	 * @param bool $prepend
	 */
	public function addLink(array $attrs, string $uniqueAttrName = null, bool $prepend = false) {
		$this->meta->addLink($attrs, $uniqueAttrName, $prepend);
	}
}

class BodyBuilderMeta {
	protected $target;
	protected $meta;
	
	/**
	 * @param HtmlBuilderMeta $htmlBuilderMeta
	 * @param string $target
	 */
	public function __construct(HtmlBuilderMeta $htmlBuilderMeta, string $target) {
		$this->target = $target;
		$this->meta = $htmlBuilderMeta;
	}
	
	/**
	 * @see HtmlBuilderMeta::addCss()
	 * @param Url|string $relativeUrl
	 * @param string $media
	 * @param string $moduleNamespace
	 * @param bool $prepend
	 * @param array $attrs
	 */
	public function addCss($relativeUrl, string $media = null, string $moduleNamespace = null, bool $prepend = false, array $attrs = null) {
		$this->meta->addCss($relativeUrl, $media, $moduleNamespace, $prepend, $attrs, $this->target);
	}
	
	public function addCssUrl($href, string $media = null, bool $prepend = false, array $attrs = null) {
		$this->meta->addCssUrl($href, $media, $prepend, $attrs, $this->target);
	}
	
	public function addCssCode($code, $prepend = false, array $attrs = null) {
		$this->meta->addCssCode($code, $prepend, $attrs, $this->target);
	}
	
	public function addAsyncJs($relativeUrl, $module = null, $prepend = false) {
		$this->meta->addAsyncJs($relativeUrl, $module, $prepend, $this->target);
	}
	
	public function addAsyncJsUrl($src, $prepend = false) {
		$this->meta->addAsyncJsUrl($src, $prepend, $this->target);
	}
	
	public function addJs($relativeUrl, string $moduleNamespace = null, bool $defer = false, bool $prepend = false,
			array $attrs = null) {
				$this->meta->addJs($relativeUrl, $moduleNamespace, $defer, $prepend, $attrs, $this->target);
	}
	
	public function addJsUrl($src, bool $defer = false, bool $prepend = false, array $attrs = null) {
		$this->meta->addJsUrl($src, $defer, $prepend, $attrs, $this->target);
	}
	
	public function addJsCode($code, bool $defer = false, $prepend = false, array $attrs = null) {
		$this->meta->addJsCode($code, $defer, $prepend, $attrs, $this->target);
	}
	
	public function addContents($html, bool $prepend = false) {
		$this->meta->addContents($html, $prepend, $this->target);
	}
}
