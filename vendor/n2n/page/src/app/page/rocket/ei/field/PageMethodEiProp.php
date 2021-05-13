<?php
namespace page\rocket\ei\field;

use rocket\impl\ei\component\prop\adapter\DraftablePropertyEiPropAdapter;
use rocket\ei\util\Eiu;
use n2n\util\type\CastUtils;
use page\bo\PageController;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use page\model\PageControllerAnalyzer;
use n2n\util\StringUtils;
use n2n\l10n\N2nLocale;
use rocket\ei\EiPropPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\mag\Mag;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;

class PageMethodEiProp extends DraftablePropertyEiPropAdapter {
	
	public function createMag(Eiu $eiu): Mag {
		$pageController = $eiu->entry()->getEntityObj();
		CastUtils::assertTrue($pageController instanceof PageController);
		
		$analyzer = new PageControllerAnalyzer(new \ReflectionClass($pageController));
		
		$ciPanelNames = array();
		$options = array();
		foreach ($analyzer->analyzeAllMethods() as $pageMethod) {
			$ciPanelNames[$pageMethod->getName()] = $pageMethod->getCiPanelNames();
			$options[$pageMethod->getName()] = StringUtils::pretty($pageMethod->getName());
		}
		
		$mag = new PageMethodEnumMag($this->getLabelLstr(), $options, null, 
				$this->isMandatory($eiu));
		$mag->setInputAttrs(array('class' => 'page-method', 'data-panel-names' => json_encode($ciPanelNames)));
		return $mag;
	}
	
	public function createUiComponent(HtmlView $view, Eiu $eiu)  {
		return $view->getHtmlBuilder()->getEsc(StringUtils::pretty(
				$eiu->field()->getValue(EiPropPath::from($this))));
	}
		
	public function isStringRepresentable(): bool {
		return true;
	}
	
	public function buildIdentityString(Eiu $eiu, N2nLocale $n2nLocale): ?string {
		return $eiu->object()->readNativValue($this);
	}
}

class PageMethodEnumMag extends EnumMag {
	
    public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$view->getHtmlBuilder()->meta()->addJs('js/page-method.js', 'page');
		
		return parent::createUiField($propertyPath, $view, $uo);
	}
}