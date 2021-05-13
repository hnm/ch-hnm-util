<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\web\ui\view\html;

use n2n\web\ui\UiComponent;
use n2n\web\ui\BuildContext;
use n2n\web\ui\SimpleBuildContext;

class HtmlSnippet implements UiComponent {
	private $contents = array();
	
	public function __construct(...$contents) {
		$this->contents = $contents;
	}

	/**
	 * @param mixed ...$contents
	 * @return \n2n\impl\web\ui\view\html\HtmlSnippet
	 */
	public function prepend(...$contents) {
		array_unshift($this->contents, ...$contents);
		return $this;
	}
	
	/**
	 * @param mixed ...$contents
	 * @return \n2n\impl\web\ui\view\html\HtmlSnippet
	 */
	public function append(...$contents) {
		array_push($this->contents, ...$contents);
		return $this;
	}
	
	public function appendLn($content) {
		$this->contents[] = $content;
		$this->contents[] = PHP_EOL;
	}
	
	public function build(BuildContext $buildContext): string {
		$html = '';
		foreach ($this->contents as $content) {
			$html .= HtmlUtils::contentsToHtml($content, $buildContext);
		}
		return $html;
	}
	
	/**
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->build(new SimpleBuildContext());
	}
}
