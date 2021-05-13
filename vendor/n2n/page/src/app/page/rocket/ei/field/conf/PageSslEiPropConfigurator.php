<?php
namespace page\rocket\ei\field\conf;

use rocket\impl\ei\component\prop\adapter\config\AdaptableEiPropConfigurator;
use rocket\ei\component\EiSetup;
use page\config\PageConfig;
use n2n\util\type\CastUtils;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\web\dispatch\mag\MagCollection;
use page\rocket\ei\field\PageSslEiProp;
use rocket\impl\ei\component\prop\adapter\config\DisplayConfig;
use rocket\ei\manage\gui\ViewMode;

class PageSslEiPropConfigurator extends AdaptableEiPropConfigurator {
	private $pageSslEiField;
	
	public function __construct(PageSslEiProp $pageSslEiField) {
		parent::__construct($pageSslEiField);
		$this->pageSslEiField = $pageSslEiField;
		$this->autoRegister();
	}
	
	public function setup(EiSetup $eiSetupProcess) {
		$pageConfig = $eiSetupProcess->getN2nContext()->getModuleConfig('page');
		CastUtils::assertTrue($pageConfig instanceof PageConfig);
		
		if (!$pageConfig->isSslSelectable()) {
			$this->pageSslEiField->setDisplayConfig(new DisplayConfig(ViewMode::none()));
		}
	}
	
	public function createMagDispatchable(N2nContext $n2nContext): MagDispatchable {
		return new MagForm(new MagCollection());
	}
	
	public function saveMagDispatchable(MagDispatchable $magDispatchable, N2nContext $n2nContext) {
	}
}