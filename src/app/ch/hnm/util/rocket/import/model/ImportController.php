<?php
namespace ch\hnm\util\rocket\import\model;

use n2n\util\StringUtils;
use rocket\op\util\OpuCtrl;
use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\l10n\DynamicTextCollection;
use n2n\persistence\orm\EntityManager;
use n2n\web\http\controller\ControllerAdapter;
use ch\hnm\util\rocket\import\bo\ImportUpload;
use ch\hnm\util\rocket\import\form\AssignationForm;
use ch\hnm\util\rocket\import\bo\Csv;
use n2n\l10n\MessageContainer;
use n2n\io\managed\impl\TmpFileManager;
use n2n\web\http\Session;
use n2n\web\http\controller\ParamGet;
use n2n\web\http\PageNotFoundException;
use n2n\persistence\orm\util\UnknownEntryException;

class ImportController extends ControllerAdapter {
	private $dtc;
	private $importDao;
	/**
	 * @var OpuCtrl
	 */
	private $opuCtrl;

	private function _init(DynamicTextCollection $dtc, ImportDao $importDao) {
		$this->dtc = $dtc;
		$this->importDao = $importDao;
	}

	public function prepare(OpuCtrl $opuCtrl) {
		$this->opuCtrl = $opuCtrl;
	}
	
	public function index(TmpFileManager $tmpFileManager, Session $session) {
		$this->applyBreadCrumbs(1);

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
		$importUpload = $this->getImportUploadById($id);
		
		$this->importDao->removeImportUpload($importUpload);
		$this->redirectToController();
	}
	
	public function doCheckImport(ParamGet $c = null, ParamGet $qn, TmpFileManager $tfm, Session $session) {
		$this->applyBreadCrumbs(1);
		
		$sessionFile = $tfm->getSessionFile($qn, $session);

		if ($sessionFile === null) {
			throw new PageNotFoundException();
		}

		$csv = new Csv($sessionFile->getFileSource()->createInputStream()->read());

		if ($sessionFile === null) {
 			throw new PageNotFoundException();
		}

		if ($c !== null) {
			$importUpload = new ImportUpload($this->opuCtrl->frame()->getEiThingPath(), $sessionFile, new \DateTime('now'));
			$this->importDao->saveImportUpload($importUpload);

			$this->redirectToController(['assign', $importUpload->getId()]);
			return;
		}
		
		$this->forward('..\view\checkImport.html', array('csv' => $csv));
	}
	
	public function doAssign(int $iuId, EntityManager $em) {
		$importUpload = $this->getImportUploadById($iuId);
		$this->applyBreadCrumbs(2);
		$csv = new Csv($importUpload->getFile()->getFileSource()->createInputStream()->read());

        $assignationForm = new AssignationForm();
        if ($this->dispatch($assignationForm, 'assign')) {
        	$assignationMap = $assignationForm->getAssignationMap();
            $importUpload->setAssignationJson(StringUtils::jsonEncode($assignationMap));
            $scalarEiProperties = $this->opuCtrl->frame()->getScalarEiProperties();
            $this->forward('..\view\assignCheck.html', array('assignationMap' => $assignationMap,
                    'iuId' => $iuId, 'scalarEiProperties' => $scalarEiProperties,
					'csvLines' => /*$csvLines = */$csv->getCsvLines(),
					'uploadedArr' => $this->buildUploadedArr($importUpload)));
            return;
        }

		$this->forward('..\view\assign.html', array('importUpload' => $importUpload,
				'csvPropertyNames' => $csv->getColumnNames(),
				'scalarEiProperties' => $this->opuCtrl->frame()->getScalarEiProperties(),
				'assignationForm' => $assignationForm));
	}

	public function doExecute(int $iuId, MessageContainer $mc) {
        $importUpload = $this->getImportUploadById($iuId);

		$this->applyBreadCrumbs(3);

		$importUpload->execute($importUpload, $this->buildUploadedArr($importUpload), $mc, $this->dtc, $this->opuCtrl->frame());

		$this->forward('..\view\confirmation.html', array ('messageContainer' => $mc));
    }

    private function buildUploadedArr(ImportUpload $importUpload) {
		if (!$importUpload->getStateJson() === null) return array();

		$uploadedLineNums = array();
		$eiuFrame = $this->opuCtrl->frame();
		foreach (StringUtils::jsonDecode($importUpload->getStateJson(), true)['uploaded'] as $entityJsonNote) {
			$uploaded = true;

			$eiSelection = null;
			try {
				$eiuFrame->lookupEiSelectionById($eiuFrame->idRepToId($entityJsonNote['idRep']));
			} catch (\TypeError $e) {
				$uploaded = false;
			} catch (UnknownEntryException $e) {
				$uploaded = false;
			}

			if ($uploaded) {
				$uploadedLineNums[$entityJsonNote['lineNum']] = $entityJsonNote['lineNum'];
			}
		};

		return $uploadedLineNums;
	}

	public function doRemoveEntries(int $iuId) {
		$importUpload = $this->getImportUploadById($iuId);
		$stateJson = StringUtils::jsonDecode($importUpload->getStateJson(), true);
		if (null === $stateJson || !isset($stateJson['state']) || $stateJson['state'] !== ImportUpload::STATE_FINISHED) {
			throw new PageNotFoundException();
		}

		$this->removeEntriesFromAssignationJsonArr($stateJson);
		$importUpload->setStateJson(StringUtils::jsonEncode(array('state' => ImportUpload::STATE_DELETED, 'uploaded' => [])));

		$this->redirectToController(null);
	}

	public function doReset(int $iuId) {
		$importUpload = $this->importDao->getImportUploadById($iuId);
		$importUpload->setStateJson(StringUtils::jsonEncode(array('state' => ImportUpload::STATE_UNFINISHED, 'uploaded' => [])));
	}

	private function removeEntriesFromAssignationJsonArr(array $stateJson) {
		if (null === $stateJson || !isset($stateJson['uploaded'])) return;

		$eiuFrame = $this->opuCtrl->frame();
		foreach ($stateJson['uploaded'] as $entityJsonNote) {
			try {
				$eiSelection = $eiuFrame->lookupEiSelectionById($eiuFrame->idRepToId($entityJsonNote['idRep']));
			} catch (UnknownEntryException $e) {
				$eiSelection = null;
			}

			if ($eiSelection !== null) {
				$eiuFrame->remove($eiSelection);
			}
		}
	}

	private function applyBreadCrumbs(int $stepCount) {
		$this->opuCtrl->applyCommonBreadcrumbs(null);
		$bcs = array();

		if ($stepCount > 0) {
			$bcs[] = new Breadcrumb($this->getUrlToController(array('')), $this->dtc->translate('rocket_import_breadcrumb'));
		}
		if ($stepCount > 1) {
			$bcs[] = new Breadcrumb($this->getUrlToController(array('assign')), $this->dtc->translate('rocket_import_assign_breadcrumb'));
		}

		if ($stepCount > 2) {
			$bcs[] = new Breadcrumb($this->getUrlToController(array('execute')), $this->dtc->translate('rocket_import_execute_breadcrumb'));
		}

		$this->opuCtrl->applyBreandcrumbs(...$bcs);
	}

	private function getImportUploadById(int $iuId, bool $mandatory = true) {
		$importUpload = $this->importDao->getImportUploadById($iuId);

		if (null === $importUpload && $mandatory) {
			throw new PageNotFoundException();
		}

		return $importUpload;
	}
}