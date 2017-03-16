<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\bo\ImportUpload;
use n2n\context\RequestScoped;
use n2n\persistence\orm\EntityManager;

class ImportDao implements RequestScoped {
	/**
	 * @var EntityManager
	 */
	private $em;

	private function _init(EntityManager $em) {
		$this->em = $em;
	}

	public function findImportContainerByEiThingPath(string $eiThingPath) {
		return $this->em->createSimpleCriteria(ImportUpload::getClass(), array('eiThingPath' => $eiThingPath))
				->toQuery()->fetchSingle();
	}

	public function getImportUploads() {
		return $this->em->createSimpleCriteria(ImportUpload::getClass())->toQuery()->fetchArray();
	}
}