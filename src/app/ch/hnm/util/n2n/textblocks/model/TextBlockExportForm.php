<?php
namespace ch\hnm\util\n2n\textblocks\model;

use n2n\web\dispatch\Dispatchable;
use n2n\core\TypeLoader;
use n2n\core\N2N;
use n2n\core\TypeNotFoundException;
use n2n\web\ui\ViewFactory;
use n2n\core\VarStore;
use n2n\io\IoUtils;
use n2n\web\http\Response;
use n2n\impl\web\dispatch\map\val\ValEnum;
use n2n\l10n\MessageContainer;
use n2n\l10n\DynamicTextCollection;
use n2n\l10n\TextCollectionLoader;
use n2n\l10n\N2nLocale;
use n2n\core\container\N2nContext;
use n2n\context\RequestScoped;
use n2n\web\dispatch\map\bind\BindingDefinition;

class TextBlockExportForm implements Dispatchable, RequestScoped {
	
	protected $selectedModuleNamespaces;
	protected $selectedLocaleIds;
	public $skipTranslated = true;
	
	private $n2nContext;
	private $moduleManager;
	
	private function _init(N2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
		$this->moduleManager = $n2nContext->getModuleManager();
	}
	
	public function getSelectedModuleNamespaces() {
		return $this->selectedModuleNamespaces;
	}

	public function setSelectedModuleNamespaces(array $selectedModuleNamespaces = null) {
		$this->selectedModuleNamespaces = $selectedModuleNamespaces;
	}

	public function getSelectedLocaleIds() {
		return $this->selectedLocaleIds;
	}

	public function setSelectedLocaleIds(array $selectedLocaleIds = null) {
		$this->selectedLocaleIds = $selectedLocaleIds;
	}
	
	private function _validation(BindingDefinition $bd) {
		$bd->val('selectedLocaleIds', new ValEnum(array_keys(self::getLocaleIdOptions())));
		$bd->val('selectedModuleNamespaces', new ValEnum(array_keys($this->getModuleNamespaceOptions())));
	}
	
	public function export(Response $response, MessageContainer $mc) {
		$defaultN2nLocale = N2nLocale::getDefault();
		foreach ($this->selectedModuleNamespaces as $selectedModuleNamespace) {
			foreach ($this->selectedLocaleIds as $localeId) {
				if (null === ($iniFilePath = $this->determineIniFilePath($selectedModuleNamespace, $defaultN2nLocale))) continue;
				$fileFsPath = $this->n2nContext->getVarStore()->requestFileFsPath(VarStore::CATEGORY_TMP, 
						'tmpl', 'textblocks', str_replace('\\', '.', $selectedModuleNamespace) . '-' . $localeId .  '.csv', true, true);
				
				$localeIniFilePath = null;
				try {
					$localeIniFilePath = $this->determineIniFilePath($selectedModuleNamespace, $localeId);
				} catch (TypeNotFoundException $e) {}
				
				$view = $this->n2nContext->lookup(ViewFactory::class)
						->create('ch\hnm\util\n2n\textblocks\view\exportedFile.csv',
								array('iniFilePath' => $iniFilePath, 
										'localeIniFilePath' => $localeIniFilePath, 'skipTranslated' => $this->skipTranslated), 
								$this->moduleManager->getModuleByNs($selectedModuleNamespace));
				
				$view->initialize();
				IoUtils::putContentsSafe($fileFsPath, $view->getContents());
				$mc->addInfo('"' . (string) $fileFsPath . '" erstellt.');
			}
		}
	}
	
	public function getModuleNamespaceOptions() {
		$moduleNamespaces = array();
// 		$defaultN2nLocale = N2nLocale::getDefault();
		
		foreach ($this->moduleManager->getModules() as $module) {
			if (in_array($module->getNamespace(), array('n2n', 'rocket', 'page'))) continue;
			$dtc = new DynamicTextCollection($module, N2N::getN2nLocales());
			if ($dtc->isEmpty()) continue;
			$moduleNamespaces[$module->getNamespace()] = $module->getModuleInfo()->getName();
		}
		return $moduleNamespaces;
	}
	
	private static function buildModuleLangNs($module) {
		return trim((string) $module, '\\') . '\\' . DynamicTextCollection::LANG_NS_EXT;	
	}
	
	public static function getLocaleIdOptions() {
		$localeIdOptions = array();
// 		$defaultLocale = N2nLocale::getDefault();
		foreach (N2N::getN2nLocales() as $locale) {
// 			if ($locale->equals($defaultLocale)) continue;
			$localeIdOptions[$locale->getId()] = $locale->getLanguage();
		}
		return $localeIdOptions;
	}
	
	private function determineIniFilePath($moduleNamespace, $localeId) {
		$n2nLocale = N2nLocale::create($localeId);
		
		try {
			return TypeLoader::getFilePathOfType($moduleNamespace . '\\' . DynamicTextCollection::LANG_NS_EXT
					. '\\' . $n2nLocale, TextCollectionLoader::LANG_INI_FILE_SUFFIX);
		} catch (TypeNotFoundException $e) {
			if ($n2nLocale->getRegionId() === null) return null;
		}
		
		try {
			return TypeLoader::getFilePathOfType($moduleNamespace . '\\' . DynamicTextCollection::LANG_NS_EXT
					. '\\' . $n2nLocale->getLanguageId(), TextCollectionLoader::LANG_INI_FILE_SUFFIX);
		} catch (TypeNotFoundException $e) {
			return null;
		}
	}
}