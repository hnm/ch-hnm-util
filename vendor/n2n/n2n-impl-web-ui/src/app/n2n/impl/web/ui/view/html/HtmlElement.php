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

use n2n\util\StringUtils;
use n2n\web\ui\UiComponent;
use n2n\web\ui\BuildContext;
use n2n\web\ui\SimpleBuildContext;

class HtmlElement implements UiComponent {
	private $tagName;
	private $attrs; 
	private $contents = array();
	
	public function __construct(string $tagName, array $attrs = null, $content = null) {
		$this->tagName = $tagName;
		$this->attrs = (array) $attrs;
		
		if ($content === null) return;
		if (is_array($content)) {
			$this->contents = $content;
		} else {
			$this->contents[] = $content;
		}
	}
	
	public function setTagName(string $tagName) {
		$this->tagName = $tagName;
	}
	
	public function getTagName() {
		return $this->tagName;
	}
	
	public function setAttrs(array $attrs) {
		$this->attrs = $attrs;
	}
	
	public function getAttrs() {
		return $this->attrs;
	}
	
	public function buildContentHtml(BuildContext $buildContext) {
		if (empty($this->contents)) return null;
		
		$contentHtml = '';
		foreach ($this->contents as $content) {
			$contentHtml .= HtmlUtils::contentsToHtml($content, $buildContext);	
		}
		return $contentHtml;
	}
	
	public function appendContent($content) {
		$this->contents[] = $content;
	}
	
	public function prependContent($content) {
		$this->contents[] = $content;
	}
	
	/**
	 * @param mixed ...$contents
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	public function append(...$contents) {
		array_push($this->contents, ...$contents);
		return $this;
	}
	
	public function appendLn($content = null) {
		if ($content !== null) {
			$this->appendContent($content);
		}
		$this->contents[] = PHP_EOL;
	}
	
	public function prependNl($content = null) {
		array_unshift($this->contents, PHP_EOL);
		if ($content !== null) {
			$this->prependContent($content);
		}
	}
	
	public function isEmpty() {
		return empty($this->contents);
	}
	
	public function build(BuildContext $buildContext): string {
		$html = '<' . HtmlUtils::hsc($this->tagName ) . self::buildAttrsHtml($this->attrs);
		
		if (!$this->isEmpty()) {
			$html .= '>' . $this->buildContentHtml($buildContext) . '</' 
					. HtmlUtils::hsc($this->tagName) . '>';
		} else {
			$html .= ' />';
		}
		
		return $html;
	}
	
	public function getContents() {
		return $this->__toString();
	}
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->build(new SimpleBuildContext());
	}
	
	public static function buildAttrsHtml(array $attrs = null) {
		$html = '';
		foreach ((array) $attrs as $name => $value) {
			if ($value === null) continue;

			try {
				$value = StringUtils::strOf($value);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Invalid attrs field ' . $name, null, $e);
			}

			if (is_numeric($name)) {
				$html .= ' ' . HtmlUtils::hsc($value);
			} else {
				$html .= ' ' . HtmlUtils::hsc((string) $name) . '="' . HtmlUtils::contentsToHtml($value, new SimpleBuildContext()) . '"';
			}
		}
		return $html;
	}
}