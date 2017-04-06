<?php 
namespace ch\hnm\util\rocket\import\form;

use n2n\reflection\annotation\AnnoInit;
use n2n\web\dispatch\Dispatchable;

class AssignationForm implements Dispatchable {
	private static function _annos(AnnoInit $ai) {
		
	}
	
	public $assignations = array();
	
	public function assign() {
	
	}
	
	public function setAssignations(array $assignations) {
		$this->assignations = $assignations;
	}
	
	public function getAssignations() {
		return $this->assignations;
	}
	
	private function _validation() {
		
	}
}