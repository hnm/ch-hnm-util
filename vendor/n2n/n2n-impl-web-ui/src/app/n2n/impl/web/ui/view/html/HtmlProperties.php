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

use n2n\util\type\attrs\Attributes;
use n2n\web\ui\UiComponent;
use n2n\io\ob\OutputBuffer;
use n2n\web\ui\ViewStuffFailedException;
use n2n\impl\web\dispatch\ui\Form;
use n2n\web\ui\BuildContext;
use n2n\web\ui\SimpleBuildContext;
use n2n\web\http\ServerPushDirective;
use n2n\util\type\attrs\DataSet;

class HtmlProperties {	
	protected $prependedAttributes;
	protected $dataSet;
	protected $contentHtmlProperties;
	protected $serverPushDirectives = array();
	
	private $buildContext;
	private $form;
	private $libraryHashCodes = array();
	private $ids = array();
	
	public function __construct() {
		$this->prependedAttributes = new DataSet();
		$this->dataSet = new DataSet();
		$this->buildContext = new SimpleBuildContext();
	}
	
	/**
	 * @param HtmlProperties|null $contentHtmlProperties
	 */
	public function setContentHtmlProperties(?HtmlProperties $contentHtmlProperties) {
		$this->contentHtmlProperties = $contentHtmlProperties;
	}
	
	/**
	 * @return \n2n\impl\web\ui\view\html\HtmlProperties|null
	 */
	public function getContentHtmlProperties() {
		return $this->contentHtmlProperties;
	}
	
	/**
	 * @param string $name
	 * @param UiComponent $value
	 * @param bool $prepend
	 */
	public function set(string $name, UiComponent $value, bool $prepend = false) {
		if ($prepend) {
			if ($this->prependedAttributes->contains($name)) return;
			$this->prependedAttributes->set($name, $value);
			$this->dataSet->remove($name);
		} else if (!$this->prependedAttributes->contains($name) 
				&& !$this->dataSet->contains($name)) {
			$this->dataSet->set($name, $value);
		}
		
		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->remove($name);
		}
	}
	
	/**
	 * @param string $name
	 * @param UiComponent $value
	 * @param bool $prepend
	 */
	public function push(string $name, UiComponent $value, bool $prepend = false) {
		if ($prepend) {
			$this->prependedAttributes->push($name, $value);
		} else {
			$this->dataSet->push($name, $value);
		}
		
// 		if ($this->contentHtmlProperties !== null) {
// 			$this->contentHtmlProperties->remove($name);
// 		}
	}
	
	public function add(string $name, string $key, UiComponent $value, bool $prepend = false) {
		if ($prepend) {
			if ($this->prependedAttributes->hasKey($name, $key)) return;
			$this->prependedAttributes->add($name, $key, $value);
			$this->dataSet->removeKey($name, $key);
		} else if (!$this->prependedAttributes->hasKey($name, $key)  
				&& !$this->dataSet->hasKey($name, $key)) {
			$this->dataSet->add($name, $key, $value);
		}

		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->removeKey($name, $key);
		}
	}
	
	/**
	 * @param string $name
	 */
	public function remove(string $name) {
		$this->prependedAttributes->remove($name);
		$this->dataSet->remove($name);
		
		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->remove($name);
		}
	}
	
	public function removeKey($name, $key) {
		$this->prependedAttributes->removeKey($name, $key);
		$this->dataSet->removeKey($name, $key);
		
		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->removeKey($name, $key);
		}
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function containsName(string $name) {
		return ($this->prependedAttributes->contains($name) || $this->dataSet->contains($name))
				|| ($this->contentHtmlProperties !== null && $this->contentHtmlProperties->containsName($name));
	}
	
	public function hasKey($name, $key) {
		return ($this->prependedAttributes->hasKey($name, $key) || $this->dataSet->hasKey($name, $key))
				|| ($this->contentHtmlProperties !== null && $this->contentHtmlProperties->hasKey($name, $key));
	}
	
	/**
	 * @return \n2n\util\type\attrs\Attributes[]
	 */
	public function getAttributesCollection() {
		$collection = array($this->prependedAttributes, $this->dataSet);
		if ($this->contentHtmlProperties !== null) {
			$collection = array_merge($collection, $this->contentHtmlProperties->getAttributesCollection());
		}
		return $collection;
	}
	
	/**
	 * @param string[] $keys
	 * @return array
	 */
	public function fetchUiComponentHtmlSnipplets(array $keys) {
		$contents = array_fill_keys($keys, array());
		
		foreach ($this->getAttributesCollection() as $dataSet) {
			foreach ($dataSet->toArray() as $name => $value) {
				if (!array_key_exists($name, $contents)) continue;
		
				if (is_array($value)) {
					foreach ($value as $key => $uiComponent) {
						$contents[$name][$key] = $uiComponent->build($this->buildContext);
					}
				} else if ($value instanceof UiComponent) {
					$contents[$name][] = $value->build($this->buildContext);
				}
		
				$dataSet->remove($name);
			}
		}
		
		return $contents;
	}
	
	public function fetchHtmlSnipplets(array $keys): array {
		$htmlSnipplets = array_fill_keys($keys, null);
		
		foreach ($this->getAttributesCollection() as $dataSet) {
			foreach ($dataSet->toArray() as $name => $value) {
				if (!array_key_exists($name, $htmlSnipplets)) continue;
		
				if (is_array($value)) {
					foreach ($value as $uiComponent) {
						$htmlSnipplets[$name] .= $uiComponent->build($this->buildContext) . "\r\n";
					}
				} else if ($value instanceof UiComponent) {
					$htmlSnipplets[$name] = $value->build($this->buildContext) . "\r\n";
				}
		
				$dataSet->remove($name);
			}
		}
		
		return $htmlSnipplets;
	}
	
	public function out(OutputBuffer $contentBuffer, BuildContext $buildContext) {
		$htmlSnipplets = $this->fetchHtmlSnipplets($contentBuffer->getBreakPointNames(), $buildContext);
				
		foreach ($htmlSnipplets as $name => $htmlSnipplet) {
			$contentBuffer->insertOnBreakPoint($name, $htmlSnipplets[$name]);
		}
	}
	
	private function getFirstHtmlSnipplet(BuildContext $buildContext) {
		foreach ($this->getAttributesCollection() as $dataSet) {
			foreach ($dataSet->toArray() as $value) {
				if (is_array($value)) {
					foreach ($value as $uiComponent) {
						return $uiComponent->build($buildContext);
					}
				} else if ($value instanceof UiComponent) {
					return $value->build($buildContext);
				}
			}
		}
		
		return null;
	}
	
	public function isEmpty() {
		return $this->dataSet->isEmpty() && $this->prependedAttributes->isEmpty();
	}
	
	public function validateForResponse() {
		if ($this->isEmpty()) return;
			
		throw new ViewStuffFailedException('Unassigned html property: ' 
				. $this->getFirstHtmlSnipplet(new SimpleBuildContext()));
	}
	
	public function registerLibrary(Library $library) {
		$hashCode = $library->hashCode();
		if (in_array($hashCode, $this->libraryHashCodes)) {
			return false;
		}
		
		$this->libraryHashCodes[] = $hashCode;
		return true;
	}
	
	/**
	 * Usually called by {@see HtmlBuilderMeta::serverPush()}.
	 * @param ServerPushDirective $serverPushDirective
	 */
	public function addServerPushDirective(ServerPushDirective $serverPushDirective) {
		$this->serverPushDirectives[$serverPushDirective->toHeader()->getHeaderStr()] = $serverPushDirective;
	}
	
	/**
	 * @return ServerPushDirective[]
	 */
	public function getServerPushDirectives() {
		return $this->serverPushDirectives;
	}
	
	public function registerId($id) {
		if (in_array($id, $this->ids)) {
			return false;
		}
		
		$this->ids[] = $id;
		return true;
	}
	
	/**
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}
	
	public function setForm(Form $form = null) {
		$this->form = $form;
	}
	
	public function merge(HtmlProperties $htmlProperties) {
		$this->prependedAttributes->append($htmlProperties->prependedAttributes);
		$this->dataSet->append($htmlProperties->dataSet);
		$this->serverPushDirectives += $htmlProperties->serverPushDirectives;
	}
}
