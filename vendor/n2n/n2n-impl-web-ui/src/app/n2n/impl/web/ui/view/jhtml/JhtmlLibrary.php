<?php
namespace n2n\impl\web\ui\view\jhtml;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\LibraryAdapter;
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;

class JhtmlLibrary extends LibraryAdapter {
	protected $bodyEnd;
	
	public function __construct(bool $bodyEnd = false) {
		$this->bodyEnd = $bodyEnd;
	}
	
	public function apply(HtmlView $view, HtmlBuilderMeta $htmlMeta) {
		$htmlMeta->addJs('js/jhtml.js', 'n2n\impl\web\ui', false, false, null,
				($this->bodyEnd ? HtmlBuilderMeta::TARGET_BODY_END : HtmlBuilderMeta::TARGET_HEAD));
	}
}
