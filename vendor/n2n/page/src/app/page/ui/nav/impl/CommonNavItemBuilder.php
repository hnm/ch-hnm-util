<?php
namespace page\ui\nav\impl;

class CommonNavItemBuilder extends NavItemBuilderAdapter {
	protected $classPrefix;
	
	public function __construct(string $classPrefix = null) {
		$this->classPrefix = $classPrefix;
	}
}