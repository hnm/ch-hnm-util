<?php
namespace ch\hnm\util\rocket\import\model;

use ch\hnm\util\rocket\import\bo\ImportUpload;
use n2n\context\RequestScoped;
use n2n\persistence\orm\EntityManager;
use n2n\io\managed\File;

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
	
	public function getImportUploadById(int $id) {
		return $this->em->createSimpleCriteria(ImportUpload::getClass(), array('id' => $id))->toQuery()->fetchSingle();
	}
	
	public function saveImportUpload(ImportUpload $iu) {
		$this->em->persist($iu);
	}
	
	public function removeImportUpload(ImportUpload $iu) {
		$this->em->remove($iu);
	}
}