<?php
namespace page\model\nav;

use n2n\web\http\controller\ControllerContext;

interface LeafContent {
	
	/**
	 * @return \page\model\nav\Leaf
	 */
	public function getLeaf(): Leaf;
	
	/**
	 * @throws \n2n\util\ex\IllegalStateException
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function getControllerContext(): ControllerContext;
	
	/**
	 * @return string 
	 */
	public function getSeTitle();
	
	/**
	 * @return string 
	 */
	public function getSeDescription();
	
	/**
	 * @return string 
	 */
	public function getSeKeywords();
	
	/**
	 * @return array
	 */
	public function getContentItemPanelNames(): array;
	
	/**
	 * @param string $panelName
	 * @return bool
	 */
	public function containsContentItemPanelName(string $panelName): bool;
	
	/**
	 * @param string $panelName
	 * @throws UnknownContentItemPanelException
	 */
	public function getContentItemsByPanelName(string $panelName): array;
}