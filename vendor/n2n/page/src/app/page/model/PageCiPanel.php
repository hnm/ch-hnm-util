<?php
namespace page\model;

use n2n\util\ex\IllegalStateException;
use n2n\util\type\ArgUtils;

class PageCiPanel {
	private $name;
	private $allowedCiClasses;
	private $min;
	private $max;
	
	public function __construct(string $name, array $allowedCiClasses = null, int $min = 0, int $max = null) {
		$this->name = $name;
		$this->setAllowedCiClassNames($allowedCiClasses);
		$this->min = $min;
		$this->max = $max;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setAllowedCiClassNames(array $allowedCiClassNames = null) {
		ArgUtils::valArray($allowedCiClassNames, \ReflectionClass::class, true);
		$this->allowedCiClasses = $allowedCiClassNames;
	}
	
	public function hasCiRestrictions(): bool {
		return $this->allowedCiClasses !== null;
	}
	
	public function getAllowedCiClasses(): array {
		if ($this->allowedCiClasses !== null) {
			return $this->allowedCiClasses;
		}
		
		throw new IllegalStateException('No ci restrictions.');
	}
	
	public function getMin(): int {
		return $this->min;
	}
	
	public function getMax() {
		return $this->max;
	}
}