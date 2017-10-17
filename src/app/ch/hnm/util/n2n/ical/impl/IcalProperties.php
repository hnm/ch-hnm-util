<?php
namespace ch\hnm\util\n2n\ical\impl;

use ch\hnm\util\n2n\ical\IcalComponent;

class IcalProperties extends IcalComponent {
	
	private $properties;
	
	public function __construct($properties) {
		$this->properties = $properties;
	}
	
	public function getProperties() {
		return $this->properties;
	}
}