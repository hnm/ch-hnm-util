<?php
namespace page\model\nav;

use n2n\l10n\N2nLocale;
use n2n\core\container\N2nContext;
use n2n\util\ex\IllegalStateException;
use n2n\util\uri\Path;
use n2n\util\uri\Url;
use n2n\util\type\ArgUtils;

interface Leaf {
	/**
	 * @return N2nLocale
	 */
	public function getN2nLocale(): N2nLocale;
	
	/**
	 * @return \page\model\nav\NavBranch
	 */
	public function getNavBranch(): NavBranch;
	
	/**
	 * @param NavBranch $navBranch
	 */
	public function setNavBranch(NavBranch $navBranch);
	
	/**
	 * @return string
	 */
	public function getName(): string;
	
	/**
	 * @return string
	 */
	public function getTitle(): string;

	/**
	 * returns the subsystem name for the navigation item, null for all
	 * used to find the navitem in the request delegation
	 *
	 *  @return string
	 */
	public function getSubsystemName();
	
	/**
	 *	If false than requesting the page of this leaf causes a 404. 
	 */
	public function isAccessible(): bool;
	
	/**
	 * @return bool 
	 */
	public function isHome(): bool;
	
	/**
	 * @return string
	 * @throws IllegalStateException::
	 */
	public function getPathPart(): string;
		
	/**
	 * @return bool 
	 */
	public function isInNavigation(): bool;
	
	public function isTargetNewWindow(): bool;
		
	/**
	 * @param UrlBuildTask $urlBuildTask
	 */
	public function prepareUrl(UrlBuildTask $urlBuildTask);
// 	/**
// 	 * @param mixed $obj
// 	 * 
// 	 * @return bool
// 	 */
// 	public function equals($obj);
	
// 	/**
// 	 * returns an unique characteristic-key
// 	 * 
// 	 * @return string
// 	 */
// 	public function getCharacterisitcKey();

	/**
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 * @param Path $cmdContextPath
	 * @return \page\model\nav\LeafContent
	 */
	public function createLeafContent(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath): LeafContent;
	
	/**
	 * @param N2nContext $n2nContext
	 * @return array
	 */
	public function createSitemapItems(N2nContext $n2nContext): array;
	
	public function __toString(): string;

	public function isIndexable(): bool;
}

class SitemapItem {
	const CHANGE_FREQ_ALWAYS = 'always';
    const CHANGE_FREQ_HOURLY = 'hourly';
    const CHANGE_FREQ_DAILY = 'daily';
    const CHANGE_FREQ_WEEKLY = 'weekly';
    const CHANGE_FREQ_MONTHLY = 'monthly';
    const CHANGE_FREQ_YEARLY= 'yearly';
    const CHANGE_FREQ_NERVER = 'never';

    private $loc;
    private $lastMod;
    private $changeFreq;
    private $priority;
	
	public function __construct(Url $loc, \DateTime $lastMod = null, string $changeFreq = null, 
			float $priority = null) {
		$this->loc = $loc;
		$this->lastMod = $lastMod;
		$this->setChangeFreq($changeFreq);
		$this->setPriority($priority);
	}
	
    /**
     * @return \n2n\util\uri\Url
     */
    public function getLoc() {
    	return $this->loc;
    }
    
    /**
     * @param Url $loc
     */
    public function setLoc(Url $loc) {
    	$this->loc = $loc;
    }
    
    /**
     * @return \DateTime
     */
    public function getLastMod() {
    	return $this->lastMod;
    }
    
    /**
     * @param \DateTime $lastMod
     */
    public function setLastMod(\DateTime $lastMod) {
    	$this->lastMod = $lastMod;
    }
    
    /**
     * @return string
     */
    public function getChangeFreq() {
    	return $this->changeFreq;
    }
    
    /**
     * @param string $changeFreq
     */
    public function setChangeFreq(string $changeFreq = null) {
    	ArgUtils::valEnum($changeFreq, self::getChangeFreqs(), null, true);
    	$this->changeFreq = $changeFreq;
    }
    
    /**
     * @return double
     */
    public function getPriority() {
    	return $this->priority;
    }
    
    public function setPriority(float $priority = null) {
    	ArgUtils::assertTrue($priority === null || ($priority >= 0 && $priority <= 1), 
    			'Argument priority must be between 1 and 0. Given: ' . $priority);
    	$this->priority = $priority;
    }
    
    public static function getChangeFreqs() {
    	return array(self::CHANGE_FREQ_ALWAYS, self::CHANGE_FREQ_HOURLY, self::CHANGE_FREQ_DAILY, 
    			self::CHANGE_FREQ_WEEKLY, self::CHANGE_FREQ_MONTHLY, self::CHANGE_FREQ_YEARLY, 
    			self::CHANGE_FREQ_NERVER);
    }
}