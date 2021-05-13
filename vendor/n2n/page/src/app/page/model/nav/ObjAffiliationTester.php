<?php
namespace page\model\nav;

/**
 * Must be serializable!
 */
interface ObjAffiliationTester {
	
	/**
	 * @param object $obj
	 * @return bool
	 */
	public function isAffiliatedWith($obj): bool;
}