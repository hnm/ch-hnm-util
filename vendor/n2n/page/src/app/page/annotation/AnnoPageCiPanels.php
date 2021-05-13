<?php
namespace page\annotation;

use n2n\reflection\annotation\MethodAnnotation;
use n2n\reflection\annotation\AnnotationTrait;
use n2n\reflection\annotation\MethodAnnotationTrait;

class AnnoPageCiPanels implements MethodAnnotation {
	use MethodAnnotationTrait, AnnotationTrait;
	
	private $names;
	
	public function __construct(string ...$names)  {
		$this->names = $names;
	}
	
	/**
	 * @return string[]
	 */
	public function getNames() {
		return $this->names;
	}
}