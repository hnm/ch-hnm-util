<?php
namespace ch\hnm\util\rocket\import\bo;

use n2n\l10n\DynamicTextCollection;
use n2n\l10n\MessageContainer;
use n2n\persistence\orm\annotation\AnnoManagedFile;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;
use n2n\io\managed\File;
use n2n\persistence\orm\annotation\AnnoDateTime;
use n2n\util\StringUtils;
use n2n\web\http\PageNotFoundException;
use rocket\ei\util\frame\EiuFrame;

class ImportUpload extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->p('file', new AnnoManagedFile());
		$ai->p('dateTime', new AnnoDateTime());
	}

	const STATE_FINISHED = 'finished';
	const STATE_UNFINISHED = 'unfinished';
	const STATE_DELETED = 'deleted';
	const STATE_ERROR = 'error';

	private $id;
	private $dateTime;
	private $eiThingPath;
	private $assignationJson;
	private $stateJson;
	private $file;

	public function __construct(string $eiThingPath = null, File $file = null, \DateTime $dateTime = null) {
		$this->eiThingPath = $eiThingPath;
		$this->file = $file;
		$this->dateTime = $dateTime;
		$this->stateJson = StringUtils::jsonEncode(array('state' => ImportUpload::STATE_UNFINISHED));
	}

	public function execute(ImportUpload $importUpload, array $uploadedArr, MessageContainer $mc, DynamicTextCollection $dtc, EiuFrame $eiuFrame) {
		$hasErrors = false;
		$assignationMap = StringUtils::jsonDecode($importUpload->getAssignationJson(), true);

		if (null === $importUpload->getFile()) {
			throw new PageNotFoundException();
		}

		$csv = new Csv($importUpload->getFile()->getFileSource()->createInputStream()->read());

		foreach ($csv->getCsvLines() as $cl) {

			if (isset($uploadedArr[$cl->getNum()])) {
				continue;
			}

			$eiuEntry = $eiuFrame->entry($eiuFrame->createNewEiSelection(false));

			foreach ($cl->getValues() as $key => $value) {
				if (isset($assignationMap[$key])) {
					$eiFieldPathStr = $assignationMap[$key];

					try {
						$eiuEntry->setScalarValue($eiFieldPathStr, $value);
					} catch (ValueIncompatibleWithConstraintsException $e) {
						$mc->addError($dtc->translate('invalid_value_for_label') . $eiuFrame->getEiMask()->getEiEngine()
								->getScalarEiDefinition()->getScalarEiPropertyByFieldPath($eiFieldPathStr)
								->getLabelLstr());
					}
				}
			}

			if (!$eiuEntry->getEiMapping()->save()) {
				$mc->addAll($eiuEntry->getEiMapping()->getMappingErrorInfo()->getMessages());
				$hasErrors = true;
			} else {
				$eiuFrame->em()->persist($eiuEntry->getLiveEntry()->getEntityObj());
				$eiuFrame->em()->flush();

				$eiuEntry->getLiveEntry()->refreshId();
				$cl->setEntityIdRep($eiuEntry->getLiveIdRep());
				$importUpload->setStateJson($csv->buildStateJson(ImportUpload::STATE_UNFINISHED));
			}
		}
		if ($hasErrors) {
			$importUpload->setStateJson($csv->buildStateJson(ImportUpload::STATE_ERROR));
		} else {
			$importUpload->setStateJson($csv->buildStateJson(ImportUpload::STATE_FINISHED));
		}

	}

	public function determineState() {
		if (!$this->stateJson) {
			return null;
		}

		$stateStr = StringUtils::jsonDecode($this->stateJson, true)['state'];
		$state = null;

		if ($stateStr === self::STATE_FINISHED) {
			return ImportUpload::STATE_FINISHED;
		}

		if ($stateStr === ImportUpload::STATE_DELETED) {
			return ImportUpload::STATE_DELETED;
		}

		if ($stateStr === ImportUpload::STATE_UNFINISHED) {
			return ImportUpload::STATE_UNFINISHED;
		}

		if ($stateStr === ImportUpload::STATE_ERROR) {
			return ImportUpload::STATE_ERROR;
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getDateTime() {
		return $this->dateTime;
	}

	public function setDateTime($dateTime) {
		$this->dateTime = $dateTime;
	}

	public function getEiThingPath() {
		return $this->eiThingPath;
	}

	public function setEiThingPath($eiThingPath) {
		$this->eiThingPath = $eiThingPath;
	}

	public function getAssignationJson() {
	    return $this->assignationJson;
    }

    public function setAssignationJson(string $assignationJson) {
	    $this->assignationJson = $assignationJson;
    }

	public function getFile() {
		return $this->file;
	}

	public function getStateJson() {
		return $this->stateJson;
	}

	public function setStateJson($stateJson) {
		$this->stateJson = $stateJson;
	}

	public function setFile($file) {
		$this->file = $file;
	}
}