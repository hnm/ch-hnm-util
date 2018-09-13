<?php
namespace ch\hnm\util\rocket\import\model;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\l10n\DynamicTextCollection;
use n2n\l10n\N2nLocale;
use n2n\web\http\controller\Controller;
use rocket\ei\component\command\control\OverallControlComponent;
use rocket\impl\ei\component\command\IndependentEiCommandAdapter;
use rocket\ei\manage\control\ControlButton;
use rocket\ei\manage\control\IconType;
use rocket\ei\util\Eiu;
use rocket\ei\manage\control\HrefControl;

class ImportEiCommand extends IndependentEiCommandAdapter implements OverallControlComponent {
	const ID_BASE = 'import';
	const CONTROL_IMPORT_KEY = 'import';

	public function getIdBase() {
		return self::ID_BASE;
	}

	public function getPrivilegeLabel(N2nLocale $n2nLocale) {
		$dtc = new DynamicTextCollection('ch\\hnm\\util', $n2nLocale);
		return $dtc->translate('import_label');
	}

	public function lookupController(Eiu $eiu): Controller {
		return $eiu->lookup(ImportController::class);
	}

	public function getOverallControlOptions(N2nLocale $n2nLocale) {
		$dtc = new DynamicTextCollection('rocket', $n2nLocale);

		return array(self::CONTROL_IMPORT_KEY => $dtc->translate('common_import_label'));
	}

	public function createOverallControls(Eiu $eiu, HtmlView $htmlView): array {
		$n2nContext = $eiu->frame()->getN2nContext();
		$eiUtils = $eiu->frame();
		$eiFrame = $eiUtils->getEiFrame();
		$httpContext = $n2nContext->getHttpContext();
		$dtc = new DynamicTextCollection('rocket', $n2nContext->getN2nLocale());
		$controllerContextPath = $httpContext->getControllerContextPath($eiUtils->getEiFrame()->getControllerContext());

		$name = $dtc->translate('import_label');
		$tooltip = $dtc->translate('import_tooltip', array('type' => $eiUtils->getGenericLabel()));

		return array(self::CONTROL_IMPORT_KEY => HrefControl::create($eiFrame, $this, null,
				new ControlButton($name, $tooltip, true, ControlButton::TYPE_SUCCESS, IconType::ICON_PLUS_CIRCLE)));
	}
}