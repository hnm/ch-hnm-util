<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\context\RequestScoped;
use rocket\core\model\Breadcrumb;

class ImportViewModel implements RequestScoped {
	private $importForm;
	private $eiThingPath;

	const IMPORT_STEPS = array(array('import', 'import'),
			array('checkimport', 'check import'),
			array('assignfields', 'assign fields'),
			array('checkassigned', 'check assigned fields'),
			array('finished', 'finished'));

	public function getImportStepBreadcrumbs() {

		$importStepBreadcrumbs = array();
		foreach (self::IMPORT_STEPS as $step) {
			$importStepBreadcrumbs[] = new Breadcrumb($step[0], $step[1]);
		}

		return $importStepBreadcrumbs;
	}

	/**
	 * @return mixed
	 */
	public function getImportForm() {
		return $this->importForm;
	}

	/**
	 * @param mixed $importForm
	 */
	public function setImportForm($importForm) {
		$this->importForm = $importForm;
	}

	/**
	 * @return mixed
	 */
	public function getEiThingPath() {
		return $this->eiThingPath;
	}

	/**
	 * @param mixed $eiThingPath
	 */
	public function setEiThingPath($eiThingPath) {
		$this->eiThingPath = $eiThingPath;
	}

	private function _onSerialize() {
		$this->importForm = null;
	}

	private function _onUnserialize(ImportForm $importForm) {
		$this->importForm = $importForm;
	}
}