<?php
namespace n2nutil\jquery;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\LibraryAdapter;
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;
use n2n\util\type\ArgUtils;

class JQueryLibrary extends LibraryAdapter {
	protected $version;
	protected $bodyEnd;

	public function __construct(int $version, bool $bodyEnd = false) {
		ArgUtils::valEnum($version, array(1, 2, 3));
		$this->version = $version;
		$this->bodyEnd = $bodyEnd;
	}

	public function apply(HtmlView $view, HtmlBuilderMeta $htmlMeta) {
		$jsName = null;
		switch ($this->version) {
			case 1:
				$jsName = 'jquery-1.12.4.min.js';
				break;
			case 2:
				$jsName = 'jquery-2.2.4.min.js';
				break;
			case 3:
				$jsName = 'jquery-3.5.0.min.js';
		}

		$htmlMeta->addJs($jsName, 'n2nutil\jquery', false, false, null,
			($this->bodyEnd ? HtmlBuilderMeta::TARGET_BODY_END : HtmlBuilderMeta::TARGET_HEAD));
	}
}