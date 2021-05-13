<?php
namespace page\ui\nav;

use page\model\NavBranchCriteria;

/**
 * <p>Factory for {@link NavComposer} which can build navigations. Each factory method creates a {@link NavComposer} with
 * a diffrent base page which is used as initial point for further configuration.</p>
 */
class Nav {
	
	/**
	 * If there is a current page according to {@link \page\model\PageState} its root page will be used as base. 
	 * Otherwise any root page will be used.
	 * 
	 * @return \page\ui\nav\NavComposer
	 */
	public static function root(): NavComposer {
		return new NavComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_ROOT));
	}
	
	/**
	 * If there is a current page according to {@link \page\model\PageState} its parent page with the lowest level
	 * which still is displayed in the navigation will be used as base.
	 * 
	 * @return \page\ui\nav\NavComposer
	 */
	public static function navRoot() {
		return new NavComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_NAV_ROOT));
	}
	
	/**
	 * The home page (page with an empty path part) will be used as base.
	 * 
	 * @return NavComposer
	 */
	public static function home() {
		return new NavComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_HOME));
	}
	
	/**
	 * The home page (page with an empty path part) of the passed subsystem will be used as base.
	 * 
	 * @param string $subsystemName
	 * @return \page\ui\nav\NavComposer
	 */
	public static function subHome(string $subsystemName = null) {
		return new NavComposer(NavBranchCriteria::createSubHome($subsystemName));
	}
	
	/**
	 * If there is a current page according to {@link \page\model\PageState} it will be used as base.
	 * Otherwise any root page will be used.
	 *
	 * @return \page\ui\nav\NavComposer
	 */
	public static function current(): NavComposer {
		return new NavComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_CURRENT));
	}
	
	/**
	 * Uses page as base which is affiliated with the passed object.
	 * 
	 * You can use any object which is related to a page. For example an object of type 
	 * {@link \page\model\nav\NavBranch}, {@link \page\model\nav\Leaf}, {@link \page\bo\PageController} etc. 
	 *  
	 * @param object $obj affiliated object
	 * @return \page\ui\nav\NavComposer
	 */
	public static function obj($obj): NavComposer {
		return new NavComposer(NavBranchCriteria::create($obj));
	}
	
	/**
	 * Uses page as base which contains passed tags.
	 * 
	 * See {@link https://support.n2n.rocks/de/page/docs/navigieren#tags tags article} for further 
	 * information about tags.
	 * 
	 * @param string ...$tagNames names of tags
	 * @return \page\ui\nav\NavComposer
	 */
	public static function tag(string ...$tagNames): NavComposer {
		return new NavComposer(NavBranchCriteria::create(null, $tagNames));
	}
	
	/**
	 * Uses page as base which is marked with passed hooks.
	 *
	 * See {@link https://support.n2n.rocks/de/page/docs/navigieren#hooks hooks article} for further 
	 * information about hooks.
	 *
	 * @param string ...$hookKeys keys of hooks
	 * @return \page\ui\nav\NavComposer
	 */
	public static function hook(string ...$hookKeys): NavComposer {
		return new NavComposer(NavBranchCriteria::create(null, null, $hookKeys));
	}
}