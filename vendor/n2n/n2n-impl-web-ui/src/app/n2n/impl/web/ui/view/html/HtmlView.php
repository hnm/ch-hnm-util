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

use n2n\web\ui\UiException;
use n2n\io\ob\OutputBuffer;
use n2n\core\N2N;
use n2n\web\ui\view\View;
use n2n\util\type\ArgUtils;
use n2n\web\http\Response;
use n2n\impl\web\dispatch\ui\FormHtmlBuilder;
use n2n\impl\web\dispatch\ui\AriaFormHtmlBuilder;
use n2n\web\ui\view\ViewStateListener;
use n2n\core\module\Module;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\ui\BuildContext;

class HtmlView extends View {
	private $htmlProperties = null;
	private $htmlBuilder;
	private $formHtmlBuilder;
	private $ariaFormHtmlBuilder;
	protected $imported = false;
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\ui\view\View::getContentType()
	 */
	public function getContentType() {
		return 'text/html; charset=' . N2N::CHARSET;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\ui\view\View::compile($contentBuffer)
	 */
	protected function compile(OutputBuffer $contentBuffer, BuildContext $buildContext) {
		$contextView = null;
		if (!$this->imported && ($buildContextView = $buildContext->getView()) instanceof HtmlView) {
			$contextView = $buildContextView;
		}
		
		$this->htmlBuilder = new HtmlBuilder($this, $contentBuffer);
		$this->formHtmlBuilder = new FormHtmlBuilder($this);
		$this->ariaFormHtmlBuilder = new AriaFormHtmlBuilder($this);
		
		$attrs = array('view' => $this, 'html' => $this->htmlBuilder, 'formHtml' => $this->formHtmlBuilder,
				'ariaFormHtml' => $this->ariaFormHtmlBuilder);
		if ($this->getN2nContext()->isHttpContextAvailable()) {
			$httpContext = $this->getHttpContext();
			$attrs['httpContext'] = $httpContext;
			$attrs['request'] = $httpContext->getRequest();
			$attrs['response'] = $httpContext->getResponse();
		}
		
		if ($contextView !== null) {
			$this->getHtmlProperties()->setForm($contextView->getHtmlProperties()->getForm());
		}
		
		$htmlProperties = $this->htmlProperties;
		$contentsBuildContext = $this->contentsBuildContext;
		parent::bufferContents($attrs,
				function (OutputBuffer $contentBuffer) use ($htmlProperties, $contentsBuildContext) {
					$htmlProperties->out($contentBuffer, $contentsBuildContext);
				});
				
		$this->htmlBuilder = null;
		$this->formHtmlBuilder = null;
		$this->ariaFormHtmlBuilder = null;
		
		if ($contextView !== null) {
			$contextView->getHtmlProperties()->merge($this->getHtmlProperties());
		}
	} 
	
// 	protected function createImportView(string $viewNameExpression, $params = null, 
// 			ViewCacheControl $viewCacheControl = null, Module $module = null) {
// 		$view = parent::createImportView($viewNameExpression, $params, $viewCacheControl, $module);
// 		if ($view instanceof HtmlView) {
// 			$view->setHtmlProperties($this->htmlProperties);
// 		}
// 		return $view;
// 	}

	public function getImport($viewNameExpression, array $params = null,
			ViewCacheControl $viewCacheControl = null, Module $module = null) {
		$view = parent::getImport($viewNameExpression, $params, $viewCacheControl, $module);
				
		if (!($view instanceof HtmlView) || $view->imported) {
			return $view;
		}
		
		if ($view->isInitialized()) {
			$this->htmlProperties->merge($view->getHtmlProperties());
			return $view;	
		}
		
		$view->imported = true;
		
		$view->getHtmlProperties()->setForm($this->getHtmlProperties()->getForm());
		$view->registerStateListener(new class($this->htmlProperties, $view) implements ViewStateListener {
			private $htmlProperties;
			private $importedView;
			
			public function __construct(HtmlProperties $htmlProperties, HtmlView $importedView) {
				$this->htmlProperties = $htmlProperties;
				$this->importedView = $importedView;
			}
			/**
			 * {@inheritDoc}
			 * @see \n2n\web\ui\view\ViewStateListener::onViewContentsBuffering()
			 */
			public function onViewContentsBuffering(\n2n\web\ui\view\View $view) {
			}
		
			/**
			 * {@inheritDoc}
			 * @see \n2n\web\ui\view\ViewStateListener::viewContentsInitialized()
			 */
			public function viewContentsInitialized(\n2n\web\ui\view\View $view) {
				$this->htmlProperties->merge($this->importedView->getHtmlProperties());
			}
		
			/**
			 * {@inheritDoc}
			 * @see \n2n\web\ui\view\ViewStateListener::onPanelImport()
			 */
			public function onPanelImport(\n2n\web\ui\view\View $view, $panelName) {
			}
		
		});
		
		return $view;
	}
	
// 	public function getOut($uiComponent) {
// 		if (!($uiComponent instanceof HtmlView)) {
// 			return parent::getOut($uiComponent);
// 		}
		
// 		if (!$uiComponent->isInitialized()) {
// 			$uiComponent->getHtmlProperties()->setForm($this->getHtmlProperties()->getForm());
// 		}
		
// 		$contents = parent::getOut($uiComponent);
		
// // 		if ($uiComponent->getHtmlProperties() !== $this->getHtmlProperties()) {
// 			$this->htmlProperties->merge($uiComponent->getHtmlProperties());
// // 		}
		
// 		return $contents;
// 	}
	
	public function setHtmlProperties(HtmlProperties $htmlProperties) {
		$this->htmlProperties = $htmlProperties;
	}
	
	/**
	 * @return HtmlProperties
	 */
	public function getHtmlProperties() {
		if ($this->htmlProperties === null) {
			$this->htmlProperties = new HtmlProperties();
		}
		
		return $this->htmlProperties;
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HtmlBuilder
	 */
	public function getHtmlBuilder() {
		return $this->htmlBuilder;
	}
	
	/**
	 * @return \n2n\impl\web\dispatch\ui\FormHtmlBuilder
	 */
	public function getFormHtmlBuilder() {
		return $this->formHtmlBuilder;
	}
	
	/**
	 * @return \n2n\impl\web\dispatch\ui\AriaFormHtmlBuilder
	 */
	public function getAriaFormHtmlBuilder() {
		return $this->ariaFormHtmlBuilder;
	}
	
// 	public function readCachedContents(ViewCacheReader $cacheReader) {
// 		parent::readCachedContents($cacheReader);
// 		$this->htmlProperties = $cacheReader->readAttributesObject();
// 	}
	
// 	public function writeCachedContents(ViewCacheWriter $cacheWriter) {
// 		parent::writeCachedContents($cacheWriter);
// 		$cacheWriter->writeAttributesObject($this->htmlProperties);
// 	}
	
	public function initializeFromCache($data) {
		ArgUtils::assertTrue(is_array($data) && isset($data['contents'])
				&& isset($data['htmlProperties']) && isset($data['htmlProperties']) 
				&& $data['htmlProperties'] instanceof HtmlProperties);

		$this->htmlProperties = $data['htmlProperties'];
		parent::initializeFromCache($data['contents']);
	}
	
	public function toCacheData() {
		return array(
				'contents' => parent::toCacheData(),				
				'htmlProperties' => $this->htmlProperties);
	}
	
	public function prepareForResponse(Response $response) {
		parent::prepareForResponse($response);
		
		foreach ($this->htmlProperties->getServerPushDirectives() as $directive) {
			$response->serverPush($directive);
		}

// 		try {
// 			$this->htmlProperties->validateForResponse();
// 		} catch (ViewStuffFailedException $e) {
// 			throw new ViewStuffFailedException('Could not send view to response: ' . $this->toKownPayloadString(), 0, $e);
// 		}
	}
	
	/**
	 * @param HtmlView $view
	 * @return \n2n\impl\web\ui\view\html\HtmlBuilder
	 */
	public static function html(HtmlView $view) {
		return $view->getHtmlBuilder();
	}
	
	/**
	 * @param HtmlView $view
	 * @return \n2n\impl\web\dispatch\ui\FormHtmlBuilder
	 */
	public static function formHtml(HtmlView $view): FormHtmlBuilder {
		return $view->getFormHtmlBuilder();
	}
	
	/**
	 * @param HtmlView $view
	 * @return \n2n\impl\web\dispatch\ui\AriaFormHtmlBuilder
	 */
	public static function ariaFormHtml(HtmlView $view): AriaFormHtmlBuilder {
		return $view->getAriaFormHtmlBuilder();
	}
}

class NoHttpControllerContextAssignetException extends UiException {
		
}

/**
 * hack to provide autocompletion in views
 */
return;

$html = new \n2n\impl\web\ui\view\html\HtmlBuilder();
$formHtml = new \n2n\impl\web\dispatch\ui\FormHtmlBuilder();
$ariaFormHtml = new \n2n\impl\web\dispatch\ui\AriaFormHtmlBuilder();

// to avoid never used warinings
$html->meta();
$formHtml->meta();
$ariaFormHtml->close();
