<?php
namespace page\rocket\ei\field\conf;

use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\dispatch\mag\MagCollection;
use rocket\ei\component\EiSetup;
use page\config\PageConfig;
use n2n\util\type\CastUtils;
use page\rocket\ei\field\PageSubsystemEiProp;
use rocket\impl\ei\component\prop\adapter\config\AdaptableEiPropConfigurator;
use n2n\l10n\DynamicTextCollection;
use rocket\ei\manage\gui\ViewMode;
use rocket\impl\ei\component\prop\adapter\config\DisplayConfig;

class PageSubsystemEiPropConfigurator extends AdaptableEiPropConfigurator {
	private $pageSubsystemEiField;
	
	public function __construct(PageSubsystemEiProp $pageSubsystemEiField) {
		parent::__construct($pageSubsystemEiField);
		$this->autoRegister();
		
		$this->pageSubsystemEiField = $pageSubsystemEiField;
	}
	
	public function setup(EiSetup $eiSetupProcess) {
		$n2nContext = $eiSetupProcess->getN2nContext();
		$pageConfig = $eiSetupProcess->getN2nContext()->getModuleConfig('page');
		CastUtils::assertTrue($pageConfig instanceof PageConfig);
		
		$dtc = new DynamicTextCollection('page', $n2nContext->getN2nLocale());
		$subsystems = $eiSetupProcess->getN2nContext()->getHttpContext()->getAvailableSubsystems();

		if (empty($subsystems)) {
			$this->pageSubsystemEiField->setDisplayConfig(new DisplayConfig(ViewMode::none()));
		}
		
		$options = array(null => $dtc->translate('all_subsystems_label'));
		foreach ($subsystems as $subsystem) {
			$displayName = $subsystem->getName() . ' (' . $subsystem->getHostName();
			if (null !== ($contextPath = $subsystem->getContextPath())) {
				$displayName .= '/' . $contextPath;
			}
			$displayName .= ')';
			
			$options[$subsystem->getName()] = $displayName;
		}
		
		
		$this->pageSubsystemEiField->setOptions($options);
		
	}
	
	public function createMagDispatchable(N2nContext $n2nContext): MagDispatchable {
		return new MagForm(new MagCollection());
	}
	
	public function saveMagDispatchable(MagDispatchable $magDispatchable, N2nContext $n2nContext) {
	
	}
}