<?php
namespace page\model;

use n2n\context\ThreadScoped;
use page\bo\Page;
use n2n\core\container\N2nContext;
use n2n\util\type\CastUtils;
use page\config\PageConfig;
use n2n\config\InvalidConfigurationException;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\web\http\ResponseCacheStore;
use n2n\web\ui\view\ViewCacheStore;
use n2n\reflection\ReflectionUtils;
use n2n\reflection\magic\MagicMethodInvoker;

class PageMonitor implements ThreadScoped {
	private $n2nContext;
	private $pageDao;
	private $pageConfig;
	private $pageEvents = array();
	
	private function _init(N2nContext $n2nContext, PageDao $pageDao) {
		$this->n2nContext = $n2nContext;
		$this->pageDao = $pageDao;
		$this->pageConfig = $this->n2nContext->getModuleConfig('page');
		CastUtils::assertTrue($this->pageConfig instanceof PageConfig);
	}
	
	public function registerRelatedChange(Page $page) {
		$objHash = spl_object_hash($page);
		if (!isset($this->pageEvents[$objHash])) {
			$this->pageEvents[$objHash] = new PageEvent(PageEvent::TYPE_UPDATE, $page);
		}
	}
	
	public function registerInsert(Page $page) {
		$this->pageEvents[spl_object_hash($page)] = new PageEvent(PageEvent::TYPE_INSERT, $page);
	}
	
	public function registerUpdate(Page $page) {
		$this->pageEvents[spl_object_hash($page)] = new PageEvent(PageEvent::TYPE_UPDATE, $page);
	}
	
	public function registerRemove(Page $page) {
		$this->pageEvents[spl_object_hash($page)] = new PageEvent(PageEvent::TYPE_REMOVE, $page);		
	}
	
	private function lookupPageListeners() {
		
		$pageListeners = array();
		foreach ($this->pageConfig->getPageListenerLookupIds() as $pageListenerLookupId) {
			$pageListener = null;
			try {
				$pageListeners[] = $pageListener = $this->n2nContext->lookup($pageListenerLookupId);
			} catch (MagicObjectUnavailableException $e) {
				throw new InvalidConfigurationException('Invalid PageListener configured: ' . $pageListenerLookupId, 0, $e);
			}
				
			if (!($pageListener instanceof PageListener)) {
				throw new InvalidConfigurationException('PageListener must implement ' . PageListener::class . ': '
						. get_class($pageListener));
			}
		}
		return $pageListeners;
	}
		
	public function flush() {
		if (empty($this->pageEvents)) {
			return;
		}
		
		$this->pageDao->clearCache();
		
		$pageListeners = $this->lookupPageListeners();
		$autoClear = false;
		
		while (null !== ($pageEvent = array_pop($this->pageEvents))) {
			if (!$this->checkForMagicCallback($pageEvent)) {
				$autoClear = true;
			}
		
			foreach ($pageListeners as $pageListener) {
				$pageListener->onPageEvent($pageEvent);
			}
		}
		
		if ($autoClear && $this->pageConfig->isCacheClearedOnPageEvent()) {
			$this->n2nContext->lookup(ResponseCacheStore::class)->clear();
			$this->n2nContext->lookup(ViewCacheStore::class)->clear();
		}
		
		foreach ($pageListeners as $pageListener) {
			$pageListener->onFlush();
		}
	}
	
	const CHANGE_CALLBACK_METHOD = '_onPageChange';
	
	private function checkForMagicCallback(PageEvent $pageEvent): bool {
		$pageContent = $pageEvent->getPage()->getPageContent();
		if ($pageContent === null) return false;
		
		$pageController = $pageContent->getPageController();
		$changeCallbackMethods = ReflectionUtils::extractMethodHierarchy(new \ReflectionClass($pageController), 
				self::CHANGE_CALLBACK_METHOD);
		
		if (empty($changeCallbackMethods)) return false;
		
		$magicMethodInvoker = new MagicMethodInvoker($this->n2nContext);
		$magicMethodInvoker->setClassParamObject(PageEvent::class, $pageEvent);
		
		foreach ($changeCallbackMethods as $method) {
			$method->setAccessible(true);
			$magicMethodInvoker->invoke($pageController, $method);
		}
		
		return true;	
	}
}

class PageEvent {
	const TYPE_INSERT = 'insert';
	const TYPE_UPDATE = 'update';
	const TYPE_REMOVE = 'remove';
	
	private $type;
	private $page;
	
	public function __construct(string $type, Page $page) {
		$this->type = $type;
		$this->page = $page;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getPage() {
		return $this->page;
	}
}