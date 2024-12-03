<?php
namespace ch\hnm\util\page\bo;

use n2n\reflection\ObjectAdapter;
use n2n\web\http\nav\UrlComposer;
use n2n\reflection\annotation\AnnoInit;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\persistence\orm\FetchType;
use page\bo\Page;
use n2n\core\container\N2nContext;
use n2n\web\http\controller\ControllerContext;
use n2n\util\uri\Url;
use page\model\nav\murl\MurlPage;
use rocket\attribute\EiType;

#[EiType]
class ExplPageLink extends ObjectAdapter implements UrlComposer {
	private static function _annos(AnnoInit $ai) {
		$ai->p('linkedPage', new AnnoManyToOne(Page::getClass(), null, FetchType::EAGER));
	}
	
	const TYPE_INTERNAL = 'internal';
	const TYPE_EXTERNAL = 'external';
	
	private $id;
	private $type = self::TYPE_INTERNAL;
	private $linkedPage;
	private $url;
	private $showExplicit = true;
	private $label;

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	/**
	 * @return Page|null
	 */
	public function getLinkedPage() {
		return $this->linkedPage;
	}

	/**
	 * @param Page|null $linkedPage
	 */
	public function setLinkedPage(?Page $linkedPage = null) {
		$this->linkedPage = $linkedPage;
	}

	/**
	 * @return Url
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param Url $url
	 */
	public function setUrl($url = null) {
		$this->url = $url;
	}

	public function getLabel() {
		return $this->label;
	}

	public function setLabel($label) {
		$this->label = $label;
	}

	public function isShowExplicit() {
		return $this->showExplicit;
	}

	public function setShowExplicit(bool $showExplicit) {
		$this->showExplicit = $showExplicit;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\nav\UrlComposer::toUrl()
	 */
	public function toUrl(N2nContext $n2nContext, ?ControllerContext $controllerContext = null,
			?string &$suggestedLabel = null): Url {
		
		if ($this->type == self::TYPE_EXTERNAL) {
			$suggestedLabel = $this->label;
			return Url::create($this->url);
		}
		
		$url = MurlPage::obj($this->linkedPage)->toUrl($n2nContext, $controllerContext, $suggestedLabel);
		if ($this->label !== null) {
			$suggestedLabel = $this->label;
		}
		
		return $url;
	}
}