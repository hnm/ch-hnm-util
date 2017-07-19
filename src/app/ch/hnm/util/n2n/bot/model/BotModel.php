<?php
namespace ch\hnm\util\n2n\bot\model;

use n2n\context\RequestScoped;
use n2n\reflection\annotation\AnnoInit;
use n2n\context\annotation\AnnoSessionScoped;

class BotModel implements RequestScoped {
	private static function _annos(AnnoInit $ai) {
		$ai->p('imageLoaded', new AnnoSessionScoped());
		$ai->p('checkImage', new AnnoSessionScoped());
		$ai->p('checkDispatchClassNames', new AnnoSessionScoped());
		
	}
	
	private $checkImage = false;
	private $imageLoaded = false;
	private $checkDispatchClassNames = array();
	
	public function isCheckImage() {
		return $this->checkImage;
	}

	public function setCheckImage($checkImage) {
		$this->checkImage = $checkImage;
	}

	public function isImageLoaded() {
		return (bool) $this->imageLoaded;
	}

	public function setImageLoaded($imageLoaded) {
		$this->imageLoaded = $imageLoaded;
	}
	
	public function addDispatchClassName(string $className) {
		$this->checkDispatchClassNames[$className] = $className;
	}
	
	public function hasDispatchClassName(string $className): bool {
		return in_array($className, $this->checkDispatchClassNames);
	}
}