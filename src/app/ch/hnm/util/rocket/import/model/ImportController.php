<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\l10n\DynamicTextCollection;
use n2n\web\http\controller\ControllerAdapter;
use rocket\spec\ei\manage\util\model\EiuCtrl;
use n2n\io\managed\impl\TmpFileManager;
use n2n\web\http\Session;
use n2n\web\http\controller\ParamGet;
use n2n\web\http\PageNotFoundException;
use ch\hnm\util\rocket\import\bo\ImportUpload;
use ch\hnm\util\rocket\import\bo\Import;
use ch\hnm\util\rocket\import\form\AssignationForm;
use n2n\web\http\controller\ParamQuery;

class ImportController extends ControllerAdapter {
	private $dtc;
	private $importDao;
	private $eiuCtrl;

	private function _init(DynamicTextCollection $dtc, ImportDao $importDao) {
		$this->dtc = $dtc;
		$this->importDao = $importDao;
	}

	public function prepare(EiuCtrl $eiuCtrl) {
		$this->eiuCtrl = $eiuCtrl;
	}
	
	public function index(TmpFileManager $tmpFileManager, Session $session) {
		$this->eiuCtrl->applyCommonBreadcrumbs(null, $this->dtc->translate('rocket_import_breadcrumb'));

		$importForm = new ImportForm();

		if ($this->dispatch($importForm)) {
			$qualifiedName = $tmpFileManager->add($importForm->getFile(), $session);
			$this->redirectToController('checkimport', array('qn' => $qualifiedName));
			return;
		}

		$this->forward('..\view\form.html', array('importForm' => $importForm,
				'importUploads' => $this->importDao->getImportUploads()));
	}

	public function doDelete(int $id) {
		if (null === ($iu = $this->importDao->getImportUploadById($id))) {
			throw new PageNotFoundException();
		}
		
		$this->importDao->removeImportUpload($iu);
		$this->redirectToController();
	}
	
	public function doCheckImport(ParamGet $c = null, ParamGet $qn, TmpFileManager $tfm, Session $session, ImportViewModel $ivm) {
		$this->eiuCtrl->applyCommonBreadcrumbs(null, $this->dtc->translate('rocket_import_breadcrumb'));
		
		$sessionFile = $tfm->getSessionFile($qn, $session);
		if ($sessionFile === null) {
			throw new PageNotFoundException();
		}
		
		$sessionFileData = $sessionFile->getFileSource()->createInputStream()->read();
		
		$csv = new Csv($sessionFileData);
		$columns = $csv->getColumnNames();
		$rows = $csv->getRows();
		
		if ($c !== null) {
			$importUpload = new ImportUpload($this->eiuCtrl->frame()->getEiThingPath(), $sessionFile, new \DateTime('now'));
			
			$this->beginTransaction();
			$this->importDao->saveImportUpload($importUpload);
			$this->commit();
			
			$eiFieldCollection = $this->eiuCtrl->frame()->getEiSpec()->getEiEngine()->getEiFieldCollection()->toArray(true);
			$import = new Import($csv, $eiFieldCollection);
			
			$this->redirectToController('assign', array('iuId' => $importUpload->getId()));
			return;
		}
		
		$this->forward('..\view\check-import.html', array('columns' => $columns, 'rows' => $rows));
	}
	
	public function doAssign(ParamQuery $iuId) {
		$assignationForm = new AssignationForm();
		if ($this->dispatch($assignationForm, 'assign')) {
			die;
		}
		
		$iuId = $iuId->toInt();
		$importUpload = $this->importDao->getImportUploadById($iuId);
		if ($importUpload === null) {
			throw new PageNotFoundException();
		}
		
		$csv = new Csv($importUpload->getFile()->getFileSource()->createInputStream()->read());
		$this->forward('..\view\assign.html', array('csvPropertyNames' => $csv->getColumnNames(),
				'scalarEiProperties' => $this->eiuCtrl->frame()->getScalarEiProperties(),
				'assignationForm' => $assignationForm));
	}
}