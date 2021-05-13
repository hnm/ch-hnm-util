<?php
namespace n2n\impl\web\dispatch\mag\model;

use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\map\val\ValEmail;

class EmailMag extends StringMag {
	public function isMultiline() {
		return false;
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		parent::setupBindingDefinition($bd);
		
		$bd->val($this->propertyName, new ValEmail());
	}
}