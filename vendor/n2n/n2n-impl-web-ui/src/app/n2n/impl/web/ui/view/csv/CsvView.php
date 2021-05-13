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
namespace n2n\impl\web\ui\view\csv;

use n2n\core\N2N;
use n2n\io\ob\OutputBuffer;
use n2n\web\ui\view\View;
use n2n\web\ui\BuildContext;

class CsvView extends View {
	private $csvBuilder;
	
	public function getContentType() {
		return 'text/csv; charset=' . N2N::CHARSET;
	}
	
	protected function compile(OutputBuffer $contentBuffer, BuildContext $buildContext) {
		$this->csvBuilder = new CsvBuilder($this);
		
		$httpContext = $this->getHttpContext();
		
		parent::bufferContents(array('view' => $this,
				'httpContext' => $httpContext, 
				'request' => $httpContext->getRequest(), 
				'response' => $httpContext->getResponse(),
				'csv' => $this->csvBuilder));
	}
	
	public function getCsvBuilder() {
		return $this->csvBuilder;
	}
	
	/**
	 * @param CsvView $view
	 * @return \n2n\impl\web\ui\view\csv\CsvBuilder
	 */
	public static function csv(CsvView $view) {
		return $view->getCsvBuilder();
	}
}

/**
 * hack to provide autocompletion in views
 */
return;
$csv = new \n2n\impl\web\ui\view\csv\CsvBuilder();
$csv->meta();