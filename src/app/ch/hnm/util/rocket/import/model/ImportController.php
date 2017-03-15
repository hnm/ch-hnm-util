<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\web\http\controller\ControllerAdapter;
use rocket\spec\ei\manage\util\model\EiuCtrl;

class ImportController extends ControllerAdapter {

	public function index(EiuCtrl $eiuCtrl) {
		$eiThingPath = $eiuCtrl->frame()->getEiThingPath();
		$importForm = new ImportForm();

			if ($this->dispatch($importForm, 'save')) {

			}

		$this->forward('..\view\form.html', array('importForm' => $importForm));
	}
}