<?php
namespace page\model;

use n2n\util\type\ArgUtils;

class PageMethod {
	private $name;
	private $unique = false;
	private $ciPanelNames = array();
	
	public function __construct(string $name, bool $unique = false, array $ciPanelNames = array()) {
		$this->name = $name;
		$this->unique = $unique;
		$this->setCiPanelNames($ciPanelNames);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setName(string $name) {
		$this->name = $name;
	}
	
	public function isUnique(): bool {
		return $this->unique;
	}
	
	public function setUnique(bool $unique) {
		$this->unique = $unique;
	}
	
	/**
	 * @return array 
	 */
	public function getCiPanelNames() {
		return $this->ciPanelNames;
	}
	
	/**
	 * @param array $pageCiPanels
	 */
	public function setCiPanelNames(array $ciPanelNames) {
		ArgUtils::valArray($ciPanelNames, 'string');
		$this->ciPanelNames = $ciPanelNames;
	}
	
	public function containsCiPanelName(string $ciPanelName) {
		return in_array($ciPanelName, $this->ciPanelNames, true);
	}
}