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
use rocket\attribute\EiPreset;
use rocket\op\spec\setup\EiPresetMode;
use rocket\attribute\impl\EiPropEnum;

#[EiType(label: 'Expliziter Seitenlink')]
#[EiPreset(EiPresetMode::EDIT_PROPS, excludeProps: ['id'])]
class ExplPageLink extends ObjectAdapter implements UrlComposer {
	private static function _annos(AnnoInit $ai) {
		$ai->p('linkedPage', new AnnoManyToOne(Page::getClass(), null, FetchType::EAGER));
	}
	
	const TYPE_INTERNAL = 'internal';
	const TYPE_EXTERNAL = 'external';
	
	private int $id;
	#[EiPropEnum([self::TYPE_INTERNAL => 'intern', self::TYPE_EXTERNAL => 'extern'], guiPropsMap: ['type' => ['internal' => ['linkedPage'], 'external' => ['url']]])]
	private string $type = self::TYPE_INTERNAL;
	private ?Page $linkedPage;
	private ?string $url;
	private bool $showExplicit = true;
	private string $label;

	public function getId(): ?int {
		return $this->id ?? null;
	}

	/**
	 * @param int $id
	 */
	public function setId(int $id) {
		$this->id = $id;
	}

	public function getType(): ?string {
		return $this->type ?? null;
	}
	
	public function setType(string $type) {
		$this->type = $type;
	}

	public function getLinkedPage(): ?Page {
		return $this->linkedPage ?? null;
	}

	public function setLinkedPage(?Page $linkedPage) {
		$this->linkedPage = $linkedPage;
	}

	public function getUrl(): ?string {
		return $this->url ?? null;
	}

	public function setUrl(?string $url) {
		$this->url = $url;
	}

	public function getLabel(): ?string {
		return $this->label ?? null;
	}

	public function setLabel(?string $label) {
		$this->label = $label;
	}

	public function isShowExplicit(): bool {
		return $this->showExplicit;
	}

	public function setShowExplicit(bool $showExplicit) {
		$this->showExplicit = $showExplicit;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\nav\UrlComposer::toUrl()
	 */
	public function toUrl(N2nContext $n2nContext, ControllerContext $controllerContext = null, 
			string &$suggestedLabel = null): Url {
		
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
