<?php
namespace page\config;

use n2n\util\type\ArgUtils;

class PageConfig {
	private $n2nLocalesActive = true;
	private $autoLocaleRedirectAllowed = true;
	private $sslSelectable = false;
	private $sslDefault = true;
	private $hooks = array();
	private $cacheClearedOnPageEvent = true;
	private $pageListenerLookupIds = array();
	private $pageControllerConfigs = array();

	/**
	 * If true the all urls pointing to pages will contain the N2nLocale of the particular page translation.
	 * @return boolean
	 */
	public function areN2nLocaleUrlsActive(): bool {
		return $this->n2nLocalesActive;
	}

	/**
	 * @see self::areN2nLocaleUrlsActive()
	 * @param bool $n2nLocalesActive
	 */
	public function setN2nLocaleUrlsActive(bool $n2nLocalesActive) {
		$this->n2nLocalesActive = $n2nLocalesActive;
	}
	
	/**
	 * If true and the user requests the {@link \page\model\nav\Leaf::isHome() home page} he will redirected to the 
	 * translation which suits his browser request locale best.  
	 * @return boolean
	 */
	public function isAutoN2nLocaleRedirectAllowed(): bool {
		return $this->autoLocaleRedirectAllowed;
	}
	
	/**
	 * @see self::isAutoN2nLocaleRedirectAllowed()
	 * @param bool $autoLocaleRedirectAllowed
	 */
	public function setAutoN2nLocaleRedirectAllowed(bool $autoLocaleRedirectAllowed) {
		$this->autoLocaleRedirectAllowed = $autoLocaleRedirectAllowed;
	}

	/**
	 * If true the rocket user can select for each page if it must be requested over ssl or not.
	 * @return bool
	 */
	public function isSslSelectable() {
		return $this->sslSelectable;
	}

	/**
	 * @see self::isSslSelectable()
	 * @param bool $sslSelectable
	 */
	public function setSslSelectable(bool $sslSelectable) {
		$this->sslSelectable = $sslSelectable;
	}

	/**
	 * If true and {@link self::isSslSelectable()} is true also the default value of the ssl option is ture when 
	 * adding pages in the rocket.
	 * @return boolean
	 */
	public function isSslDefault() {
		return $this->sslDefault;
	}

	/**
	 * @see self::isSslDefault()
	 * @param bool $sslDefault
	 */
	public function setSslDefault(bool $sslDefault) {
		$this->sslDefault = $sslDefault;
	}

	/**
	 * You can define the avialable hooks which are available for the rocket user. See 
	 * {@link https://support.n2n.rocks/de/page/docs/navigieren#hooks Hook article} for more information.
	 * @return array
	 */
	public function getHookKeys() {
		return array_keys($this->hooks);
	}

	public function getHooks() {
		return $this->hooks;	
	}
	
	/**
	 * @see self::getHookKeys()
	 * @param array $hooks
	 */
	public function setHooks(array $hooks) {
		$this->hooks = $hooks;
	}
	
	/**
	 * If true the whole response cache and view cache gets cleared on every {@link \page\event\PageEvent}.
	 * @return bool
	 */
	public function isCacheClearedOnPageEvent() {
		return $this->cacheClearedOnPageEvent;
	}
	
	public function setCacheClearedOnPageEvent(bool $cacheClearedOnPageEvent) {
		$this->cacheClearedOnPageEvent = $cacheClearedOnPageEvent;
	}
	
	/**
	 * Lookup ids for custom {@link \page\model\PageListener}.
	 * @return string[]
	 */
	public function getPageListenerLookupIds() {
		return $this->pageListenerLookupIds;
	}
	
	public function setPageListenerLookupIds(array $pageListenerLookupIds) {
		ArgUtils::valArray($pageListenerLookupIds, 'scalar');
		$this->pageListenerLookupIds = $pageListenerLookupIds;
	}
	
	public function addPageControllerConfig(PageControllerConfig $pageControllerConfig) {
		$this->pageControllerConfigs[$pageControllerConfig->getEiSpecId()] = $pageControllerConfig;
	}
	
	/**
	 * @return PageControllerConfig[]
	 */
	public function getPageControllerConfigs() {
		return $this->pageControllerConfigs;
	}
	
	/**
	 * @param string $eiSpecId
	 * @return PageControllerConfig
	 */
	public function getPageControllerConfigByEiSpecId(string $eiSpecId) {
		if (isset($this->pageControllerConfigs[$eiSpecId])) {
			return $this->pageControllerConfigs[$eiSpecId];
		}
		
		return null;
	}
}