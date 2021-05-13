<?php
namespace page\model\nav;

use n2n\l10n\N2nLocale;

class LeafFilter {
	private $n2nLocale;
	private $subsystemName;
	
	public function __construct(N2nLocale $n2nLocale, string $subsystemName = null) {
		$this->n2nLocale = $n2nLocale;
		$this->subsystemName = $subsystemName;
	}
	
	public function getN2nLocale() {
		return $this->n2nLocale;
	}
	
	public function findHome(array $navBranches) {
		$acceptableLeaf = null;
		if (null !== ($leaf = $this->findHomeR($navBranches, $acceptableLeaf))) {
			return $leaf;
		}
		
		return $acceptableLeaf;
	}
		
	private function findHomeR(array $navBranches, Leaf &$acceptableLeaf = null) {
		foreach ($navBranches as $navBranch) {
			if ($navBranch->containsLeafN2nLocale($this->n2nLocale)) {
				$leaf = $navBranch->getLeafByN2nLocale($this->n2nLocale);
				
				if ($leaf->isHome()) {
					if ($leaf->getSubsystemName() === $this->subsystemName) {
						return $leaf;
					}
					
					if ($leaf->getSubsystemName() === null) {
						$acceptableLeaf = $leaf;
					}
				}
			}
			
			if (null !== ($leaf = $this->findHomeR($navBranch->getChildren(), $acceptableLeaf))) {
				return $leaf;
			}
		}
		
		return null;
	}	
}