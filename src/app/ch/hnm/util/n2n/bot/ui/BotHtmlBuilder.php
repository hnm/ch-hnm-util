<?php
namespace ch\hnm\util\n2n\bot\ui;

use n2n\impl\web\ui\view\html\HtmlView;
use ch\hnm\util\n2n\bot\model\BotModel;
use n2n\impl\web\ui\view\html\HtmlElement;
use ch\hnm\util\n2n\bot\model\BotUtils;
use n2n\impl\web\ui\view\html\HtmlUtils;

class BotHtmlBuilder {
	private $view;
	private $html;
	private $formHtml;
	/**
	 * @var BotModel
	 */
	private $botModel;
	
	public function __construct(HtmlView $view) {
		$this->view = $view;
		$this->html = $view->getHtmlBuilder();
		$this->formHtml = $view->getFormHtmlBuilder();
		$this->botModel = $view->lookup('ch\hnm\util\n2n\bot\model\BotModel');
	}
	
	public function getHiddenImage(?array $attrs = null) {
		$this->botModel->setCheckImage(true);
		$this->botModel->addDispatchClassName($this->formHtml->meta()->getForm()->getDispatchTarget()->getDispatchClassName());
		return new HtmlElement('img', HtmlUtils::mergeAttrs(
				['src' => BotUtils::buildHiddenImageUrl($this->view->getN2nContext())], $attrs));
	}
	
	public function hiddenImage(?array $attrs = null) {
		$this->view->out($this->getHiddenImage($attrs));
	}
}