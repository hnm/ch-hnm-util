<?php
namespace n2nutil\jquery;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\LibraryAdapter; 
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;
use n2n\util\type\ArgUtils;

class FancyBoxLibrary extends LibraryAdapter {
	private $version;
	private $bodyEnd;
	
	public function __construct(int $version, bool $bodyEnd = false) {
		ArgUtils::valEnum($version, array(2, 3));
		$this->version = $version;
		$this->bodyEnd = $bodyEnd;
	}
	
	public function apply(HtmlView $view, HtmlBuilderMeta $htmlMeta) {
		$jsName = null;
		switch ($this->version) {
			case 2:
				$jsName = 'fancybox/fancybox-2/js/jquery.fancybox.js';
				$cssName = 'fancybox/fancybox-2/css/jquery.fancybox.css';
				break;
			case 3:
				$jsName = 'fancybox/fancybox-3/dist/jquery.fancybox.min.js';
				$cssName = 'fancybox/fancybox-3/dist/jquery.fancybox.min.css';
				break;
		}
		
		$htmlMeta->addLibrary(new JQueryLibrary(3, $this->bodyEnd));
		
		$htmlMeta->addCss($cssName, 'screen', 'n2nutil\jquery', false, null,
				($this->bodyEnd ? HtmlBuilderMeta::TARGET_BODY_END : HtmlBuilderMeta::TARGET_HEAD));
		$htmlMeta->addJs($jsName, 'n2nutil\jquery', false, false, null,
				($this->bodyEnd ? HtmlBuilderMeta::TARGET_BODY_END : HtmlBuilderMeta::TARGET_HEAD));
	}	
}