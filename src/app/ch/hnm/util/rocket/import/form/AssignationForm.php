<?php 
namespace ch\hnm\util\rocket\import\form;

use n2n\web\dispatch\Dispatchable;

class AssignationForm implements Dispatchable {
	public $assignationMap = array();

	public function assign() {
	
	}
	
	public function setAssignationMap(array $assignations) {
		$this->assignationMap = $assignations;
	}

	public function getAssignationMap() {
		return $this->assignationMap;
	}

	private function _validation() {
		
	}
}