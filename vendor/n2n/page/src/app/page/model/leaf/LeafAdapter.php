<?php
namespace page\model\leaf;

use page\model\nav\Leaf;
use n2n\l10n\N2nLocale;
use n2n\util\ex\IllegalStateException;
use page\bo\Page;
use page\model\nav\NavBranch;
use page\model\nav\UrlBuildTask;
use n2n\core\container\N2nContext;

abstract class LeafAdapter implements Leaf {
	protected $n2nLocale;
	protected $navBranch;
	protected $name;
	protected $title;
	protected $pathPart;
	protected $subsystemName;
	protected $accessible = true;
	protected $inNavigation = true;
	protected $targetNewWindow = false;
	protected $indexable = true;

	public function __construct(N2nLocale $n2nLocale, string $name) {
		$this->n2nLocale = $n2nLocale;
		$this->name = $name;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getN2nLocale()
	 */
	public function getN2nLocale(): N2nLocale {
		return $this->n2nLocale;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getNavBranch()
	 */
	public function getNavBranch(): NavBranch {
		if ($this->navBranch !== null) {
			return $this->navBranch;
		}
		
		throw new IllegalStateException('No NavBranch assigned.');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::setNavBranch($navBranch)
	 */
	public function setNavBranch(NavBranch $navBranch) {
		$this->navBranch = $navBranch;
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getName()
	 */
	public function getName(): string {
		return $this->name;
	}
	
	public function setName(string $name) {
		$this->name = $name;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getTitle()
	 */
	public function getTitle(): string {
		if ($this->title !== null) {
			return $this->title;
		}
		
		return $this->name;
	}
	
	public function setTitle(string $title = null) {
		$this->title = $title;
	}

	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getSubsystemName()
	 */
	public function getSubsystemName() {
		return $this->subsystemName;
	}
	
	public function setSubsystemName(string $subsystemName = null) {
		$this->subsystemName = $subsystemName;
	}

	public function isAccessible(): bool {
		return $this->accessible;
	}
	
	public function setAccessible(bool $accessible) {
		$this->accessible = $accessible;
	}
	
	public function isHome(): bool {
		return $this->pathPart === null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::getPathPart()
	 */
	public function getPathPart(): string {
		if ($this->pathPart !== null) {
			return $this->pathPart;
		}
		
		throw new IllegalStateException('No pathPart for home leaf available.');
	}
	
	public function setPathPart(string $pathPart = null) {
		$this->pathPart = $pathPart;
	}
	
	public function isInNavigation(): bool {
		return $this->inNavigation;
	}
	
	public function setInNavigation(bool $inNavigation) {
		$this->inNavigation = $inNavigation;
	}
	
	public function isTargetNewWindow(): bool {
		return $this->targetNewWindow;
	}
	
	public function setTargetNewWindow(bool $targetNewWindow) {
		$this->targetNewWindow = $targetNewWindow;
	}
	
	public function prepareUrl(UrlBuildTask $urlBuildTask) {
	}
	
	public function createSitemapItems(N2nContext $n2nContext): array {
		return array();
	}

	public function setIndexable(bool $indexable) {
		$this->indexable = $indexable;
	}

	public function isIndexable(): bool {
		return $this->indexable;
	}

	public function __toString(): string {
		return (new \ReflectionClass($this))->getShortName() . ' ' . $this->name . ' in ' . $this->n2nLocale;
	}
}