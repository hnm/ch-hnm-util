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
namespace n2n\impl\web\ui\view\json;

use n2n\web\ui\view\View;
use n2n\io\ob\OutputBuffer;
use n2n\web\ui\BuildContext;

class JsonView extends View {
	private $jsonBuilder = null;
	/* (non-PHPdoc)
	 * @see \n2n\web\ui\view\View::getContentType()
	 */
	public function getContentType() {
		return 'application/json';
	}

	protected function compile(OutputBuffer $contentBuffer, BuildContext $buildContext) {
		$this->jsonBuilder = new JsonBuilder($this);
		parent::bufferContents(array('view' => $this, 'httpContext' => $this->getHttpContext(), 
				'request' => $this->getHttpContext()->getRequest(), 
				'response' => $this->getHttpContext()->getResponse(), 'json' => $this->jsonBuilder));
		$this->jsonBuilder = null;
	}
	/**
	 * @return \n2n\impl\web\ui\view\json\JsonBuilder
	 */
	public function getJsonBuilder() {
		return $this->jsonBuilder;
	}
	
	public static function json(JsonView $view) {
		return $view->getJsonBuilder();
	}
}
/**
 * hack to provide autocompletion in views
 */
return;
$json = new \n2n\impl\web\ui\view\json\JsonBuilder();
