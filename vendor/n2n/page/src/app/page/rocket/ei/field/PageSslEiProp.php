<?php
namespace page\rocket\ei\field;

use rocket\impl\ei\component\prop\bool\BooleanEiProp;
use rocket\ei\component\prop\indepenent\EiPropConfigurator;
use page\rocket\ei\field\conf\PageSslEiPropConfigurator;
use rocket\impl\ei\component\prop\adapter\config\DisplayConfig;

class PageSslEiProp extends BooleanEiProp {
	
	public function getTypeName(): string {
		return 'Ssl ScriptField (Page)';
	}
	
	public function setDisplayConfig(DisplayConfig $displayConfig) {
		$this->displayConfig = $displayConfig;
	}
	
	public function createEiPropConfigurator(): EiPropConfigurator {
		return new PageSslEiPropConfigurator($this);
	}
}