<?php
namespace n2nutil\jquery;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\HtmlBuilderMeta;

class JQueryUiLibrary extends JQueryLibrary {

	public function __construct(int $version, bool $bodyEnd = false) {
		parent::__construct($version, $bodyEnd);
	}

	public function apply(HtmlView $view, HtmlBuilderMeta $htmlMeta) {
		parent::apply($view, $htmlMeta);
		
		$htmlMeta->addJs('ui/jquery-ui.min.js', 'n2nutil\jquery', false, false, null,
				($this->bodyEnd ? HtmlBuilderMeta::TARGET_BODY_END : HtmlBuilderMeta::TARGET_HEAD));
	}
}