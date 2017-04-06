<?php
namespace ch\hnm\util\n2n\textblocks\controller;

use ch\hnm\util\n2n\textblocks\model\TextBlockExportForm;
use n2n\web\http\controller\ControllerAdapter;

class TextBlockController extends ControllerAdapter {
	
	public function index(TextBlockExportForm $textBlockExportForm) {
		
		if ($this->dispatch($textBlockExportForm, 'export')) {
			$this->refresh();
			return;
		}
		
		$this->forward('\ch\hnm\util\n2n\textblocks\view\export.html', 
				array('textBlockExportForm' => $textBlockExportForm));
	}
}