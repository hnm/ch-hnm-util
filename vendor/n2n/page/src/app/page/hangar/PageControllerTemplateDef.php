<?php
namespace page\hangar;

use hangar\api\HangarTemplateDef;
use phpbob\representation\PhpClass;
use phpbob\representation\PhpTypeDef;
use page\bo\PageController;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\impl\web\dispatch\mag\model\MagCollectionArrayMag;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\impl\web\dispatch\mag\model\StringArrayMag;
use n2n\util\type\CastUtils;
use page\annotation\AnnoPage;
use page\annotation\AnnoPageCiPanels;
use n2n\web\hangar\WebTemplateDef;
use hangar\api\Huo;

class PageControllerTemplateDef implements HangarTemplateDef {
	const PROP_NAME_PAGE_METHODS = 'pageMethods';
	const PROP_NAME_METHOD_NAME = 'methodName';
	const PROP_NAME_CI_PANELS = 'ciPanels';
	
	public function getName(): string {
		return 'PageController';
	}
	
	public function applyTemplate(Huo $huo, PhpClass $phpClass, MagDispatchable $magDispatchable = null) {
		$phpClass->setSuperClassTypeDef(PhpTypeDef::fromTypeName(PageController::class));
		
		WebTemplateDef::applyResponseCacheClearerValue($phpClass, $magDispatchable);
		
		$pageMethodMagDispatchables = $magDispatchable->getPropertyValue(self::PROP_NAME_PAGE_METHODS);
	
		foreach ($pageMethodMagDispatchables as $pageMethodMagDispatchable) {
			CastUtils::assertTrue($pageMethodMagDispatchable instanceof MagDispatchable);
			$methodName = $pageMethodMagDispatchable->getPropertyValue('methodName');
			if (empty($methodName)) continue;
			
			$phpClass->createPhpMethod($methodName);
			$methodAnnoCollection = $phpClass->getPhpAnnotationSet()->getOrCreatePhpMethodAnnoCollection($methodName)->resetPhpAnnos();
			$phpClass->createPhpUse(AnnoPage::class);
			$methodAnnoCollection->createPhpAnno(AnnoPage::class);
			
			$contentItemPanels = $pageMethodMagDispatchable->getPropertyValue(self::PROP_NAME_CI_PANELS);
			if (!empty($contentItemPanels)) {
				$phpClass->createPhpUse(AnnoPageCiPanels::class);
				$phpAnno = $methodAnnoCollection->createPhpAnno(AnnoPageCiPanels::class);
				foreach ($contentItemPanels as $contentItemPanelName) {
					$phpAnno->createPhpAnnoParam($contentItemPanelName, true);
				}
			}
		}			
	}
	
	public function createMagDispatchable(): ?MagDispatchable {
		$magCollection = new MagCollection();
		
		$magCollectionArrayMag = new MagCollectionArrayMag('Page Methods', function() {
			$magCollection = new MagCollection();
			$magCollection->addMag(self::PROP_NAME_METHOD_NAME, new StringMag('Method Name'));
			$magCollection->addMag(self::PROP_NAME_CI_PANELS, new StringArrayMag('Content Item Panels'));
			
			return new MagForm($magCollection);
		});
		
		WebTemplateDef::addResponseCacheClearerMag($magCollection);
		$magCollection->addMag(self::PROP_NAME_PAGE_METHODS, $magCollectionArrayMag);
		
		return new MagForm($magCollection);
	}
}