<?php
namespace page\bo;

use n2n\persistence\orm\annotation\AnnoEntityListeners;
use n2n\reflection\ObjectAdapter;
use n2n\web\http\controller\Controller;
use n2n\reflection\annotation\AnnoInit;
use n2n\persistence\orm\annotation\AnnoInheritance;
use n2n\persistence\orm\InheritanceType;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\web\http\controller\impl\ControllingUtilsTrait;
use n2n\web\http\controller\ControllerContext;
use n2n\web\http\controller\ActionInvokerFactory;
use n2n\web\http\controller\ControllerInterpreter;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\annotation\AnnoTransient;
use rocket\impl\ei\component\prop\translation\Translator;
use n2n\l10n\N2nLocale;
use n2n\util\type\ArgUtils;
use page\model\PageMonitor;
use n2n\web\http\controller\InterceptorFactory;
use page\model\nav\NavBranch;

abstract class PageController extends ObjectAdapter implements Controller {
	use ControllingUtilsTrait;
	
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoEntityListeners(PageEntityListener::getClass()));
		$ai->c(new AnnoInheritance(InheritanceType::JOINED));
		$ai->p('pageContent', new AnnoOneToOne(PageContent::getClass(), 'pageController', CascadeType::PERSIST));
		$ai->p('pageControllerTs', new AnnoOneToMany(PageControllerT::getClass(), 'pageController', CascadeType::ALL,
				null, true));
		$ai->p('controllingUtils', new AnnoTransient());
	}
	
	private $id;
	private $pageContent;
	private $pageControllerTs;
	private $methodName;

	private function _prePersist(PageMonitor $pageMonitor) {
		if ($this->pageContent !== null) {
			$pageMonitor->registerRelatedChange($this->pageContent->getPage());
		}
	}
	
	private function _preUpdate(PageMonitor $pageMonitor) {
		if ($this->pageContent !== null) {
			$pageMonitor->registerRelatedChange($this->pageContent->getPage());
		}
	}
	
	private function _preRemove(PageMonitor $pageMonitor) {
		if ($this->pageContent !== null) {
			$pageMonitor->registerRelatedChange($this->pageContent->getPage());
		}
	}
	
	public final function getId() {
		return $this->id;
	}

	public final function setId(int $id) {
		$this->id = $id;
	}
	
	/**
	 * @return PageContent
	 */
	public final function getPageContent() {
		return $this->pageContent;
	}

	public final function setPageContent(PageContent $pageContent) {
		$this->pageContent = $pageContent;
	}
	
	public final function getPageControllerTs() {
		return $this->pageControllerTs;
	}
	
	public final function setPageControllerTs(\ArrayObject $pageControllerTs) {
		$this->pageControllerTs = $pageControllerTs;
	}
	
	/**
	 * @param N2nLocale ...$n2nLocales
	 * @return PageControllerT
	 */
	public final function pageControllerT(N2nLocale ...$n2nLocales) {
		return Translator::find($this->pageControllerTs, ...$n2nLocales);
	}
	
	public final function getMethodName() {
		return $this->methodName;
	}
	
	public final function setMethodName(string $methodName) {
		return $this->methodName = $methodName;
	}

	public final function execute(ControllerContext $controllerContext): bool {
		$this->init($controllerContext);

		$request = $this->getRequest();
		$invokerFactory = new ActionInvokerFactory(
				$controllerContext->getCmdPath(), $controllerContext->getCmdContextPath(), $request,
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				$request->getAcceptRange(), $this->getN2nContext());
		$invokerFactory->setConstantValues($controllerContext->getParams());
		$interpreter = new ControllerInterpreter(new \ReflectionClass($this), $invokerFactory,
				new InterceptorFactory($this->getN2nContext()));
		
		$this->resetCacheControl();
		
		if (!$this->intercept(...$interpreter->findControllerInterceptors())) {
			return true;
		}
		
		$prepareInvokers = $interpreter->interpret(ControllerInterpreter::DETECT_PREPARE_METHOD);
		foreach ($prepareInvokers as $prepareInvoker) {
			$this->invokerInfo = $prepareInvoker;
			if ($this->intercept(...$prepareInvoker->getInterceptors())) {
				$prepareInvoker->getInvoker()->invoke($this);
			} else {
				return true;
			}
		}

		$invokerInfo = $interpreter->interpretCustom($this->getMethodName());
		if ($invokerInfo === null) return false;
		
		$this->cu()->setInvokerInfo($invokerInfo);
		if ($this->intercept(...$invokerInfo->getInterceptors())) {
			$invokerInfo->getInvoker()->invoke($this);
		}
		return true;
	}
	
	public function getTagNames(): array {
		$additionalTagNames = $this->getAdditionalTagNames();
		ArgUtils::valArrayReturn($additionalTagNames, $this, 'getAdditionalTagNames', 'string');
		return array_merge(array(get_class($this), $this->methodName), $additionalTagNames);
	}
	
	protected function getAdditionalTagNames(): array {
		return array();
	}
	
	public final function navBranchCreated(NavBranch $navBranch) {
		$this->enhanceNavBranch($navBranch);
	}
	
	protected function enhanceNavBranch(NavBranch $navBranch) {
	}
	
// 	/**
// 	 * Creates the Controller which gets executed
// 	 * 
// 	 * @param $n2nContext N2nContext
// 	 * @return Controller
// 	 */
// 	public abstract function delegateRequest(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath, ControllingPlan $controllingPlan);
}