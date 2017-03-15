<?php
namespace ch\hnm\util\rocket\import\model;

use n2n\web\http\controller\ControllerAdapter;
use rocket\spec\ei\manage\util\model\EiuCtrl;

class ImportController extends ControllerAdapter {

	public function index(EiuCtrl $eiuCtrl) {
		$this->forward('');

		test((string) $eiuCtrl->frame()->getEiThingPath());
	}
}