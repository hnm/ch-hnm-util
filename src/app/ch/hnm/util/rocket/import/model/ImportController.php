<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\l10n\DynamicTextCollection;
use n2n\web\http\controller\ControllerAdapter;
use rocket\core\model\Breadcrumb;
use rocket\spec\ei\manage\gui\EiSelectionGui;
use rocket\spec\ei\manage\util\model\EiuCtrl;

class ImportController extends ControllerAdapter {
	private $dtc;
	private $importDao;

	private function _init(DynamicTextCollection $dtc, ImportDao $importDao) {
		$this->dtc = $dtc;
		$this->importDao = $importDao;
	}

	public function index(EiuCtrl $eiuCtrl) {
		$eiuCtrl->applyCommonBreadcrumbs(null, $this->dtc->translate('rocket_import_breadcrumb'));

		$importForm = new ImportForm();

		if ($this->dispatch($importForm, 'save')) {
			$this->redirectToController(['checkimport', $importForm->getQualifiedFileName()]);
			return;
		}

		$this->forward('..\view\form.html', array('importForm' => $importForm,
				'importUploads' => $this->importDao->getImportUploads()));
	}

	public function doCheckImport(ImportViewModel $ivm) {

		$csv = CsvFileReader::read($ivm->getImportForm()->getFile());

		$this->forward('..\view\check-import.html', array('ivm' => $ivm));
	}
}