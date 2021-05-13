<?php
namespace page\rocket\ei\field\conf;

use n2n\core\container\N2nContext;

use rocket\impl\ei\component\prop\ci\conf\ContentItemsEiPropConfigurator;

class PageContentItemsEiPropConfigurator extends ContentItemsEiPropConfigurator {
	
	public function createMagCollection(N2nContext $n2nContext) {
		$optionCollection = parent::createMagCollection($n2nContext);
		$optionCollection->removeOptionByPropertyName(self::ATTR_PANELS_KEY);
		return $optionCollection;
	}
}