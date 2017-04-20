<?php 
namespace ch\hnm\util\rocket\import\form;

use n2n\reflection\annotation\AnnoInit;
use n2n\web\dispatch\Dispatchable;

class AssignationForm implements Dispatchable {
	private static function _annos(AnnoInit $ai) {
		
	}
	
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