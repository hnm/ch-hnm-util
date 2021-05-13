<?php
namespace page\annotation;

use n2n\reflection\annotation\MethodAnnotation;
use n2n\reflection\annotation\MethodAnnotationTrait;
use n2n\reflection\annotation\AnnotationTrait;

class AnnoPage implements MethodAnnotation {
	use MethodAnnotationTrait, AnnotationTrait;
	
	private $unique;
	
	public function __construct(bool $unique = false) {
		$this->unique = $unique;
	}
	
	/**
	 * @return bool
	 */
	public function isUnique() {
		return $this->unique;
	}
}