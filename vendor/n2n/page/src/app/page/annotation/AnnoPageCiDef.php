<?php
// namespace page\annotation;

// use n2n\reflection\ReflectionUtils;
// use rocket\impl\ei\component\prop\ci\model\ContentItem;
// use n2n\reflection\annotation\ClassAnnotation;
// use n2n\reflection\annotation\ClassAnnotationTrait;
// use n2n\reflection\annotation\AnnotationTrait;
// use n2n\util\type\ArgUtils;

// class AnnoPageCiDef implements ClassAnnotation {
// 	use ClassAnnotationTrait, AnnotationTrait;
	
// 	private $panelName;
// 	private $allowedCiClasses = array();
// 	private $min;
// 	private $max;
	
// 	public function __construct(string $panelName, array $allowedCiClassNames = null, int $min = 0, int $max = null)  {
// 		$this->panelName = $panelName;
		
// 		ArgUtils::valArray($allowedCiClassNames, array('string', \ReflectionClass::class), true);
		
// 		foreach ($allowedCiClassNames as $allowedCiClassName) {
// 			$allowedCiClass = null;
// 			if ($allowedCiClassName instanceof \ReflectionClass) {
// 				$allowedCiClass = $allowedCiClassName;
// 			} else {
// 				try {
// 					$allowedCiClass = ReflectionUtils::createReflectionClass($allowedCiClassName);
// 				} catch (\n2n\core\TypeNotFoundException $e) {
// 					throw new \InvalidArgumentException('Unknown ContentItem type: ' . $allowedCiClassName);
// 				}
// 			}
			
// 			if (!$allowedCiClass->isSubclassOf(ContentItem::class)) {
// 				throw new \InvalidArgumentException('ContentItem type must extend ' . ContentItem::class . ': ' 
// 						. $allowedCiClass->getName());
// 			}
			
// 			$this->allowedCiClasses[] = $allowedCiClass;
// 		}
		
// 		$this->min = $min;
// 		$this->max = $max;
// 	}
	
// 	/**
// 	 * @return string
// 	 */
// 	public function getPanelName(): string {
// 		return $this->panelName;
// 	}
	
// 	/**
// 	 * @return \ReflectionClass[] 
// 	 */
// 	public function getAllowedCiClasses() {
// 		return $this->allowedCiClasses;
// 	}
	
// 	/**
// 	 * @return int
// 	 */
// 	public function getMin() {
// 		return $this->min;
// 	}
	
// 	/**
// 	 * @return int
// 	 */
// 	public function getMax() {
// 		return $this->max;
// 	}
// }