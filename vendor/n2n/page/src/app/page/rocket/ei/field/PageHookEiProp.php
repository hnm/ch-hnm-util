<?php
namespace page\rocket\ei\field;

use rocket\ei\util\Eiu;
use n2n\util\type\CastUtils;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use n2n\web\dispatch\mag\Mag;
use rocket\ei\component\prop\indepenent\EiPropConfigurator;
use rocket\impl\ei\component\prop\enum\EnumEiProp;
use page\rocket\ei\field\conf\PageHookEiPropConfigurator;

class PageHookEiProp extends EnumEiProp {

	public function createEiPropConfigurator(): EiPropConfigurator {
		return new PageHookEiPropConfigurator($this);
	}
	
	public function isMandatory(Eiu $eiu): bool {
		return false;
	}
	
	public function createMag(Eiu $eiu): Mag {
		$mag = parent::createMag($eiu);
		CastUtils::assertTrue($mag instanceof EnumMag);
		
		if (null !== ($characteristicsKey = $eiu->entry()->getValue($this))){
			$mag->setOptions(array_merge(array($characteristicsKey => $characteristicsKey), $mag->getOptions()));
		}
		
		return $mag;
	}
}