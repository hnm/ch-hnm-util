<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\form\ImportForm;
use n2n\core\N2N;
use n2n\core\VarStore;
use n2n\io\IoUtils;
use n2n\io\managed\File;
use n2n\io\managed\impl\FileFactory;
use n2n\l10n\DynamicTextCollection;
use n2n\persistence\orm\EntityManager;
use n2n\reflection\property\ValueIncompatibleWithConstraintsException;
use n2n\web\http\controller\ControllerAdapter;
use rocket\spec\ei\EiFieldPath;
use rocket\spec\ei\manage\util\model\EiuCtrl;
use n2n\io\managed\impl\TmpFileManager;
use n2n\web\http\Session;
use n2n\web\http\controller\ParamGet;
use n2n\web\http\PageNotFoundException;
use ch\hnm\util\rocket\import\bo\ImportUpload;
use ch\hnm\util\rocket\import\bo\Import;
use ch\hnm\util\rocket\import\form\AssignationForm;
use n2n\web\http\controller\ParamQuery;
use n2n\l10n\MessageContainer;
use n2n\impl\web\ui\view\json\JsonBuilder;
use n2n\impl\web\ui\view\json\JsonView;
use n2n\io\managed\impl\engine\TmpFileEngine;
use n2n\io\fs\FsPath;
use rocket\spec\ei\manage\util\model\EiuFrame;

class ImportController extends ControllerAdapter {
	private $dtc;
	private $importDao;
	/**
	 * @var EiuCtrl
	 */
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
			
			$this->importDao->saveImportUpload($importUpload);

			$scalarEiProperties = $this->eiuCtrl->frame()->getScalarEiProperties();
			$import = new Import($csv, $scalarEiProperties);

			$this->redirectToController(['assign', $importUpload->getId()]);
			return;
		}
		
		$this->forward('..\view\check-import.html', array('columns' => $columns, 'rows' => $rows));
	}
	
	public function doAssign(int $iuId, EntityManager $em) {
        $importUpload = $this->importDao->getImportUploadById($iuId);
        if ($importUpload === null) {
            throw new PageNotFoundException();
        }

        $assignationForm = new AssignationForm();
        $seps = $this->eiuCtrl->frame()->getScalarEiProperties();

        if ($this->dispatch($assignationForm, 'assign')) {
            $assignationMap = $assignationForm->getAssignationMap();
            $importUpload->setAssignationJson(json_encode($assignationMap));
            $this->forward('..\view\assign-check.html', array('assignationMap' => $assignationMap,
                    'iuId' => $iuId));
            return;
        }

		$csv = new Csv($importUpload->getFile()->getFileSource()->createInputStream()->read());
		$this->forward('..\view\assign.html', array('csvPropertyNames' => $csv->getColumnNames(),
				'scalarEiProperties' => $seps,
				'assignationForm' => $assignationForm));
	}

	public function doExecute(int $iuId, MessageContainer $mc) {
        $importUpload = $this->importDao->getImportUploadById($iuId);
        if ($importUpload === null) {
            throw new PageNotFoundException();
        }

        $assignationMap = json_decode($importUpload->getAssignationJson(), true);
        $csv = new Csv($importUpload->getFile()->getFileSource()->createInputStream()->read());

        $eiuFrame = $this->eiuCtrl->frame();

        foreach ($csv->getRows() as $row) {
            $eiuEntry = $eiuFrame->entry($eiuFrame->createNewEiSelection(false));

            foreach ($row as $key => $value) {
				$value = utf8_encode($value);
                if (isset($assignationMap[$key])) {
                    $eiFieldPathStr = $assignationMap[$key];
                    try {
						$eiuEntry->setScalarValue($eiFieldPathStr, $value);
					} catch (ValueIncompatibleWithConstraintsException $e) {
                    	$mc->addError("Invalid value for " . $eiuFrame->getEiMask()->getEiEngine()
								->getScalarEiDefinition()->getScalarEiPropertyByFieldPath($eiFieldPathStr)
								->getLabelLstr());
					}
                }
            }

            if (!$eiuEntry->getEiMapping()->save()) {
				$mc->addAll($eiuEntry->getEiMapping()->getMappingErrorInfo()->getMessages());
            } else {
				$eiuFrame->em()->persist($eiuEntry->getLiveEntry()->getEntityObj());
				$eiuFrame->em()->flush();
			}
        }

		$this->forward('..\view\confirmation.html', array ('messageContainer' => $mc));
    }
}