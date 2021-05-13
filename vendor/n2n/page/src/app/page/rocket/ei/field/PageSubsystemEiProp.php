<?php
namespace page\rocket\ei\field;

use n2n\l10n\DynamicTextCollection;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use rocket\impl\ei\component\prop\enum\EnumEiProp;
use rocket\ei\util\Eiu;
use n2n\web\dispatch\mag\Mag;
use rocket\ei\component\prop\indepenent\EiPropConfigurator;
use page\rocket\ei\field\conf\PageSubsystemEiPropConfigurator;
use n2n\util\type\CastUtils;
use rocket\impl\ei\component\prop\adapter\config\DisplayConfig;
use rocket\ei\manage\gui\DisplayDefinition;

class PageSubsystemEiProp extends EnumEiProp {
	
	public function getTypeName(): string {
		return 'Subsystem';
	}
		
	public function setDisplayConfig(DisplayConfig $displayConfig) {
		$this->displayConfig = $displayConfig;
	}
	
	public function createEiPropConfigurator(): EiPropConfigurator {
		return new PageSubsystemEiPropConfigurator($this);
	}
	
	public function buildDisplayDefinition(Eiu $eiu): ?DisplayDefinition {
		if (1 == count($this->getOptions())) return null;
		
		return parent::buildDisplayDefinition($eiu);
	}
	
	public function createMag(Eiu $eiu): Mag {
		$enumMag = parent::createMag($eiu);
		CastUtils::assertTrue($enumMag instanceof EnumMag);
		
		if ($eiu->entry()->isNew()) {
			return $enumMag;
		}
		
		$attrs = [];
		$attrs['class'] = 'rocket-critical-input';
		$dtc = new DynamicTextCollection('page', $eiu->frame()->getN2nLocale());
		$attrs['data-confirm-message'] = $dtc->translate('field_subsystem_unlock_confirm');
		$attrs['data-edit-label'] =  $dtc->translate('common_edit_label');
		$attrs['data-cancel-label'] =  $dtc->translate('common_cancel_label');
		$enumMag->setInputAttrs($attrs);
		
		return $enumMag;
	}
}