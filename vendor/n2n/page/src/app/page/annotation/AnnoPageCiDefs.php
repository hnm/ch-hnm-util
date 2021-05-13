<?php
// namespace page\annotation;

// use n2n\reflection\annotation\ClassAnnotation;
// use n2n\reflection\annotation\ClassAnnotationTrait;
// use n2n\reflection\annotation\AnnotationTrait;

// class AnnoPageCiDefs implements ClassAnnotation {
// 	use ClassAnnotationTrait, AnnotationTrait;
	
// 	private $annoPageCiDefs;
	
// 	public function __construct(AnnoPageCiDef ...$annoPageCiDefs) {
// 		$this->annoPageCiDefs = $annoPageCiDefs;
// 		$this->registerSubAnnotations($annoPageCiDefs);
// 	}
	
// 	/**
// 	 * @return \page\annotation\AnnoPageCiDef[]
// 	 */
// 	public function getAnnoPageCiDefs() {
// 		return $this->annoPageCiDefs;
// 	}
// }