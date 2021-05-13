<?php
namespace page\model\nav\murl;

use page\model\NavBranchCriteria;

/**
 * <p>MurlPage creates a {@link PageUrlComposer} which can be used to build Urls to pages. It can be used like 
 * {@link \n2n\web\http\nav\ContextUrlComposer}. For example to link a page with 
 * {@link \n2n\impl\web\ui\view\html\HtmlBuilder::link()} or 
 * {@link \n2n\web\http\controller\impl\ControllingUtilsTrait::redirect()}</p> 
 * 
 * <p>
 * <strong>Example of usage in a {@link https://support.n2n.rocks/de/n2n/docs/html HtmlView}</strong>
 * <pre>
 * &lt;?php $html-&gt;link(MurlPage::tag('some', 'tags')-&gt;pathExt(array('path-part-1', 'path-part-2'))) ?&gt;
 * </pre>
 * </p>
 */
class MurlPage {
	
	/**
	 * Creates a {@link PageUrlComposer} which points to the root page of the current page according to 
	 * {@link \page\model\PageState}. If there is no current page any root page will be used.
	 *
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function root() {
		return new PageUrlComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_ROOT));
	}
	
	/**
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function navRoot() {
		return new PageUrlComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_NAV_ROOT));
	}
	
	/**
	 * Creates a {@link PageUrlComposer} which points to the current page according to 
	 * {@link \page\model\PageState}. If there is no current page any root page will be used.
	 *
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function current() {
		return new PageUrlComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_CURRENT));
	}
	
	/**
	 * @param string $subsystemName
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function home() {
		return new PageUrlComposer(NavBranchCriteria::createNamed(NavBranchCriteria::NAMED_HOME));
	}
	
	public static function subHome(string $subsystemName = null) {
		return new PageUrlComposer(NavBranchCriteria::createSubHome($subsystemName));
	}
	
	/**
	 * <p>Creates a {@link PageUrlComposer} which points to a page which is affiliated with the passed object.</p>
	 * 
	 * <p>You can use any object which is related to a page. For example an object of type 
	 * {@link \page\model\nav\NavBranch}, {@link \page\model\nav\Leaf}, {@link \page\bo\PageController} etc.</p>
	 *  
	 * @param object $obj affiliated object
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function obj($obj) {
		return new PageUrlComposer(NavBranchCriteria::create($obj));
	}
	
	/**
	 * Creates a {@link PageUrlComposer} which points to a page which contains passed tags.
	 * 
	 * See {@link https://support.n2n.rocks/de/page/docs/navigieren#tags tags article} for further 
	 * information about tags.
	 * 
	 * @param string ...$tagNames names of tags
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function tag(string ...$tagNames) {
		return new PageUrlComposer(NavBranchCriteria::create(null, $tagNames));
	}
	
	/**
	 * Creates a {@link PageUrlComposer} which points to a page which is marked with passed hooks.
	 *
	 * See {@link https://support.n2n.rocks/de/page/docs/navigieren#hooks hooks article} for further 
	 * information about hooks.
	 *
	 * @param string ...$hookKeys keys of hooks
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function hook(string ...$hookKeys) {
		return new PageUrlComposer(NavBranchCriteria::create(null, null, $hookKeys));
	}
	
	/**
	 * @param string $id Page id
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public static function id(string $id) {
		return new PageUrlComposer(NavBranchCriteria::create(null, null, null, $id));
	}
}
