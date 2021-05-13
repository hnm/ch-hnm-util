<?php
namespace page\config;

use n2n\util\type\attrs\Attributes;
use n2n\core\module\ConfigDescriberAdapter;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\dispatch\mag\model\StringArrayMag;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\core\N2N;
use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\util\type\attrs\LenientAttributeReader;
use page\bo\PageController;
use rocket\impl\ei\component\prop\ci\conf\CiConfigUtils;
use n2n\impl\web\dispatch\mag\model\MagCollectionMag;
use rocket\core\model\Rocket;
use n2n\util\type\CastUtils;
use rocket\spec\Spec;
use page\model\PageControllerAnalyzer;
use n2n\util\type\TypeConstraint;
use n2n\util\type\ArgUtils;
use rocket\impl\ei\component\prop\ci\model\PanelConfig;

class PageDescriber extends ConfigDescriberAdapter {
	const ATTR_LOCALES_ACTIVE_KEY = 'localeUrls';
	const ATTR_LOCALES_ACTIVE_DEFAULT = true;
	const ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_KEY = 'autoLocaleRedirect';
	const ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_DEFAULT = true;
	const ATTR_SSL_SELECTABLE_KEY = 'sslSelectable';
	const ATTR_SSL_SELECTABLE_DEFAULT = false;
	const ATTR_SSL_DEFAULT_KEY = 'sslDefault';
	const ATTR_SSL_DEFAULT_DEFAULT = true;
	const ATTR_HOOK_KEYS_KEY = 'hooks';
	const ATTR_CACHE_CLEARED_ON_PAGE_EVENT_KEY = 'cacheClearedOnPageEvent';
	const ATTR_CACHE_CLEARED_ON_PAGE_EVENT_DEFAULT = true;
	const ATTR_PAGE_LISTENER_LOOKUP_IDS_KEY = 'pageListenerLookupIds';
	const ATTR_PAGE_CONTROLLERS_KEY = 'pageControllers';
	const ATTR_PAGE_CONTROLLER_LABEL_KEY = 'label';
	const ATTR_PAGE_CONTROLLER_CI_PANELS_KEY = 'ciPanels';
	
	public function createMagDispatchable(): MagDispatchable {
		$lar = new LenientAttributeReader($this->readCustomAttributes());
		
		$magCollection = new MagCollection();
		
		$magCollection->addMag(self::ATTR_LOCALES_ACTIVE_KEY, 
				new BoolMag('N2nLocales active (if checked, the locales will appear in the URL)',
						$lar->getBool(self::ATTR_LOCALES_ACTIVE_KEY, self::ATTR_LOCALES_ACTIVE_DEFAULT)));
		
		$magCollection->addMag(self::ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_KEY, 
				new BoolMag('Auto locale Redirect', $lar->getBool(self::ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_KEY, 
								self::ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_DEFAULT)));
		
		$magCollection->addMag(self::ATTR_SSL_SELECTABLE_KEY, new BoolMag('Ssl Option in Page Navi Available?',
				$lar->getBool(self::ATTR_SSL_SELECTABLE_KEY, self::ATTR_SSL_SELECTABLE_DEFAULT)));
		
		$magCollection->addMag(self::ATTR_SSL_DEFAULT_KEY, new BoolMag('Default Value for ssl', 
				$lar->getBool(self::ATTR_SSL_DEFAULT_KEY, self::ATTR_SSL_DEFAULT_DEFAULT)));
		
		$magCollection->addMag(self::ATTR_HOOK_KEYS_KEY, 
				new StringArrayMag('Available Hooks', $lar->getScalarArray(self::ATTR_HOOK_KEYS_KEY)));

		$magCollection->addMag(self::ATTR_SSL_SELECTABLE_KEY, new BoolMag('Clear Cache on Page Event',
				$lar->getBool(self::ATTR_CACHE_CLEARED_ON_PAGE_EVENT_KEY, self::ATTR_CACHE_CLEARED_ON_PAGE_EVENT_DEFAULT)));
		
		$magCollection->addMag(self::ATTR_PAGE_LISTENER_LOOKUP_IDS_KEY, 
				new StringArrayMag('Page Listener Lookup Ids', $lar->getScalarArray(self::ATTR_PAGE_LISTENER_LOOKUP_IDS_KEY)));
		
		$magCollection->addMag(self::ATTR_PAGE_CONTROLLERS_KEY, new MagCollectionMag('PageControllers', 
				$this->createPcMagCollection($lar->getArray(self::ATTR_PAGE_CONTROLLERS_KEY))));
				
		return new MagForm($magCollection);
	}
		
	private function createPcMagCollection(array $pageControllersAttrs) {
		$specManager = $this->n2nContext->lookup(Rocket::class)->getSpec();
		CastUtils::assertTrue($specManager instanceof Spec);
		
		$pageControllerEiSpec = $specManager->getEiTypeByClass(PageController::getClass());
		
		$ciConfigUtils = CiConfigUtils::createFromN2nContext($this->n2nContext);
		
		$lar = new LenientAttributeReader(new Attributes($pageControllersAttrs));
		
		$magCollection = new MagCollection();
		foreach ($pageControllerEiSpec->getAllSubEiTypes() as $subEiSpec) {
			$pageControllerAttrs = $lar->getArray($subEiSpec->getId());
			$pcLar = new LenientAttributeReader(new Attributes($pageControllerAttrs));
			
			$pcMagCollection = new MagCollection();
			$magCollection->addMag($subEiSpec->getId(), new MagCollectionMag($subEiSpec->getEiMask()->getLabelLstr(), 
					$pcMagCollection));
			
// 			$pcMagCollection->addMag(new StringMag(self::ATTR_PAGE_CONTROLLER_LABEL_KEY, 'Label', 
// 					$pcLar->getString(self::ATTR_PAGE_CONTROLLER_LABEL_KEY)));
			
			$panelNames = (new PageControllerAnalyzer($subEiSpec->getEntityModel()->getClass()))->analyzeAllCiPanelNames();
			if (empty($panelNames)) {
				continue;
			}
			
			$panelsAttrs = $pcLar->getArray(self::ATTR_PAGE_CONTROLLER_CI_PANELS_KEY);
			$panelsMagCollection = new MagCollection();
			foreach ($panelNames as $panelName) {
				$panelMagCollection = $ciConfigUtils->createPanelConfigMagCollection(false);
				if (isset($panelsAttrs[$panelName])) {
					$panelMagCollection->writeValues($ciConfigUtils->buildPanelConfigMagCollectionValues(
							$panelsAttrs[$panelName]));
				}
				$panelsMagCollection->addMag($panelName, new MagCollectionMag($panelName, $panelMagCollection));	
			}
			$pcMagCollection->addMag(self::ATTR_PAGE_CONTROLLER_CI_PANELS_KEY, new MagCollectionMag('Panels', $panelsMagCollection));
		}
	
		return $magCollection;
	}
	
	public function saveMagDispatchable(MagDispatchable $magDispatchable) {
		$values = $magDispatchable->getMagCollection()->readValues();
		
		$ciConfigUtils = CiConfigUtils::createFromN2nContext($this->n2nContext);
		
		foreach ($values[self::ATTR_PAGE_CONTROLLERS_KEY] as $pagecontrollerKey => $pageControllerValues) {
			if (empty($pageControllerValues)) continue;
			
			foreach ($pageControllerValues[self::ATTR_PAGE_CONTROLLER_CI_PANELS_KEY] as $key => $ciPanelValues) {
				$values[self::ATTR_PAGE_CONTROLLERS_KEY][$pagecontrollerKey][self::ATTR_PAGE_CONTROLLER_CI_PANELS_KEY][$key] = $ciConfigUtils->buildPanelConfigAttrs($ciPanelValues);
			}
		}
		
		$attributes = new Attributes($values);
		$attributes->removeNulls(true);
		
		$this->writeCustomAttributes($attributes);
	}
	
    /**
     * {@inheritDoc}
     * @see \n2n\core\module\ConfigDescriber::buildCustomConfig()
     */
	public function buildCustomConfig() {
		$attributes = $this->readCustomAttributes();
		
		$pageConfig = new PageConfig();
		$pageConfig->setN2nLocaleUrlsActive($attributes->optBool(self::ATTR_LOCALES_ACTIVE_KEY, 
				self::ATTR_LOCALES_ACTIVE_DEFAULT));
		$pageConfig->setAutoN2nLocaleRedirectAllowed($attributes->optBool(self::ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_KEY, 
				self::ATTR_AUTO_LOCALE_REDIRECT_ACTIVE_DEFAULT));
		$pageConfig->setSslDefault($attributes->optBool(self::ATTR_SSL_SELECTABLE_KEY, 
				self::ATTR_SSL_SELECTABLE_DEFAULT));
		$pageConfig->setSslDefault($attributes->optBool(self::ATTR_SSL_DEFAULT_KEY, 
				self::ATTR_SSL_DEFAULT_DEFAULT));
		
		$hooks = array();
		foreach ($attributes->getScalarArray(self::ATTR_HOOK_KEYS_KEY, false) as $key => $label) {
			if (is_numeric($key)) {
				$hooks[$label] = $label;
			} else {
				$hooks[$key] = $label;
			}
		}
		$pageConfig->setHooks($hooks);
		$pageConfig->setPageListenerLookupIds($attributes->getScalarArray(self::ATTR_PAGE_LISTENER_LOOKUP_IDS_KEY, 
				false));
		
		$pageControllerConfigs = array();
		foreach ($attributes->getArray(self::ATTR_PAGE_CONTROLLERS_KEY, false, array(), 
				TypeConstraint::createArrayLike('array')) as $pageControllerEiSpecId => $pageControllerAttrs) {
			$ciPanelConfigs = array();
			$pageControllerAttributes = new Attributes($pageControllerAttrs);
			foreach ($pageControllerAttributes->getArray(self::ATTR_PAGE_CONTROLLER_CI_PANELS_KEY, false, array(), 
					TypeConstraint::createArrayLike('array')) as $panelName => $ciPanelAttrs) {
				$ciPanelConfigs[] = CiConfigUtils::createPanelConfig($ciPanelAttrs, $panelName);
			}
				
			$pageConfig->addPageControllerConfig(new PageControllerConfig($pageControllerEiSpecId, $ciPanelConfigs));
		}
		
		return $pageConfig;
	}
}

class PageControllerConfig {
	private $eiSpecId;
	private $ciPanelConfigs;
	
	public function __construct(string $eiSpecId, array $ciPanelConfigs) {
		ArgUtils::valArray($ciPanelConfigs, PanelConfig::class);
		$this->eiSpecId = $eiSpecId;
		$this->ciPanelConfigs = $ciPanelConfigs;
	}
	
	public function getEiSpecId() {
		return $this->eiSpecId;
	}
	
	public function getCiPanelConfigs() {
		return $this->ciPanelConfigs;
	}
	

	public function getCiPanelConfigByPanelName(string $panelName) {
		foreach ($this->ciPanelConfigs as $ciPanelConfig) {
			if ($ciPanelConfig->getName() === $panelName) {
				return $ciPanelConfig;
			}
		}
		
		return null;
	}
	
}