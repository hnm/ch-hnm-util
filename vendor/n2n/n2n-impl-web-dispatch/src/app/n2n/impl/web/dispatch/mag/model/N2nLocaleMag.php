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
namespace n2n\impl\web\dispatch\mag\model;

use n2n\core\N2N;
use n2n\l10n\N2nLocale;
use n2n\util\type\ArgUtils;

class N2nLocaleMag extends EnumMag {
	private $displayN2nLocale;

	/**
	 * N2nLocaleMag constructor.
	 * @param string $label
	 * @param null $displayN2nLocale
	 * @param null $default
	 * @param bool $required
	 * @param array|null $inputAttrs
	 */
	public function __construct(string $label, $displayN2nLocale = null, $default = null, $required = false, array $inputAttrs = null) {
		parent::__construct($label, $this->getN2nLocaleMags(), $default, $required, $inputAttrs);
		$this->displayN2nLocale = $displayN2nLocale;
	}

	/**
	 * @param array $n2nLocales
	 */
	public function setN2nLocales(array $n2nLocales) {
		ArgUtils::valArray($n2nLocales, N2nLocale::class);
		$optArr = array();
		foreach ($n2nLocales as $n2nLocale) {
			$optArr[$n2nLocale->getId()] = $n2nLocale;
		}
		$this->setOptions($optArr);
	}

	/**
	 * @param mixed $formValue
	 */
	public function setFormValue($formValue) {
		$this->value = N2nLocale::build($formValue);
	}

	/**
	 * @return null|string
	 */
	public function getFormValue() {
		return $this->value !== null ? (string) $this->value : null;
	}

	/**
	 * @return N2nLocale[]
	 */
	private function getN2nLocaleMags() {
		$N2nLocaleMags = array();

		foreach (N2N::getN2nLocales() as $n2nLocale) {
			$N2nLocaleMags[(string) $n2nLocale] = $n2nLocale->getName($this->displayN2nLocale);
		}

		return $N2nLocaleMags;
	}
}
