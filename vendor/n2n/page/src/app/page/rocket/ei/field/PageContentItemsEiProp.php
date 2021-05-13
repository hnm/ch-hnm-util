<?php
namespace page\rocket\ei\field;

use n2n\persistence\orm\property\EntityProperty;
use page\bo\Page;
use n2n\impl\persistence\orm\property\ToManyEntityProperty;
use n2n\util\type\ArgUtils;
use rocket\impl\ei\component\prop\ci\model\ContentItem;
use page\bo\PageControllerT;
use rocket\ei\util\Eiu;
use rocket\impl\ei\component\prop\ci\ContentItemsEiProp;
use n2n\util\type\CastUtils;
use page\bo\PageController;
use page\model\PageControllerAnalyzer;
use page\config\PageConfig;
use rocket\impl\ei\component\prop\ci\model\PanelConfig;
use n2n\util\StringUtils;
use rocket\core\model\Rocket;
use rocket\ei\EiPropPath;
use rocket\impl\ei\component\prop\ci\model\ContentItemGuiField;
use rocket\ei\manage\gui\GuiField;
use rocket\ei\manage\gui\ui\DisplayItem;

class PageContentItemsEiProp extends ContentItemsEiProp {
	/**
	 * @var \page\config\PageConfig
	 */
	private $pageConfig;
	
	public function getTypeName(): string {
		return 'ContentItems (Page)';
	}
	
	protected function getDisplayItemType(): string {
		return DisplayItem::TYPE_PANEL;
	}
	
	public function setEntityProperty(EntityProperty $entityProperty = null) {
		parent::setEntityProperty($entityProperty);
		
		ArgUtils::assertTrue($entityProperty instanceof ToManyEntityProperty
				&& $entityProperty->getEntityModel()->getClass()->getName() === PageControllerT::class
				&& $entityProperty->getTargetEntityModel()->getClass()->getName() === ContentItem::class);
	}
	
	public function determinePanelConfigs(Eiu $eiu) {
		$relationMapping = $eiu->entry()->getValue(EiPropPath::from($this)->poped()
				->pushed('pageController'));
		if ($relationMapping === null) {
			return array();
		}
		$pageController = $pageController = $relationMapping->getEiObject()->getEiEntityObj()->getEntityObj();
		CastUtils::assertTrue($pageController instanceof PageController);
		
		$rocket = $eiu->frame()->getEiFrame()->getN2nContext()->lookup(Rocket::class);
		CastUtils::assertTrue($rocket instanceof Rocket);
		$specManager = $rocket->getSpec();
		
		$pageControllerClass = new \ReflectionClass($pageController);
		$analyzer = new PageControllerAnalyzer($pageControllerClass);
		$pageConfig = $eiu->frame()->getEiFrame()->getN2nContext()->getModuleConfig(Page::NS);
		CastUtils::assertTrue($pageConfig instanceof PageConfig);
		
		$pageControllerConfig = $pageConfig->getPageControllerConfigByEiSpecId(
				$specManager->getEiTypeByClass($pageControllerClass)->getId());
		
		$panelConfigs = array();
		foreach ($analyzer->analyzeAllCiPanelNames() as $panelName) {
			if ($pageControllerConfig !== null && 
					null !== ($panelConfig = $pageControllerConfig->getCiPanelConfigByPanelName($panelName))) {
				$panelConfigs[$panelName] = $panelConfig;
				continue;
			}
			
			$panelConfigs[$panelName] = $panelConfig = new PanelConfig($panelName, StringUtils::pretty($panelName));
		}
		return $panelConfigs;
	}
	
	public function buildGuiField(Eiu $eiu): ?GuiField {
	    $contentItemGuiField = parent::buildGuiField($eiu);
		CastUtils::assertTrue($contentItemGuiField instanceof ContentItemGuiField);
	
		if (empty($contentItemGuiField->getPanelConfigs())) {
			return null;
		}
		
		return $contentItemGuiField;
	}	
}