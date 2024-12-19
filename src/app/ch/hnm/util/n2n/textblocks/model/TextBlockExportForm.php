<?php
namespace ch\hnm\util\n2n\textblocks\model;

use n2n\web\dispatch\Dispatchable;
use n2n\core\TypeLoader;
use n2n\core\N2N;
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
	
	private $moduleManager;
	private $data;
	
	
	private function _init(N2nContext $n2nContext) {
		$this->moduleManager = $n2nContext->getModuleManager();
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getSelectedModuleNamespaces() {
		return $this->selectedModuleNamespaces;
	}

	public function setSelectedModuleNamespaces(?array $selectedModuleNamespaces = null) {
		$this->selectedModuleNamespaces = $selectedModuleNamespaces;
	}

	public function getSelectedLocaleIds() {
		return $this->selectedLocaleIds;
	}

	public function setSelectedLocaleIds(?array $selectedLocaleIds = null) {
		$this->selectedLocaleIds = $selectedLocaleIds;
	}
	
	private function _validation(BindingDefinition $bd) {
		$bd->val('selectedLocaleIds', new ValEnum(array_keys(self::getLocaleIdOptions())));
		$bd->val('selectedModuleNamespaces', new ValEnum(array_keys($this->getModuleNamespaceOptions())));
	}
	
	public function export(Response $response, MessageContainer $mc) {
		$defaultN2nLocale = N2nLocale::getDefault();
		
		foreach ($this->selectedLocaleIds as $localeId) {
			foreach ($this->selectedModuleNamespaces as $selectedModuleNamespace) {
				$defaultIniData = [];
				if (!isset($data[$selectedModuleNamespace][$defaultN2nLocale->getId()])) {
					$tc = TextCollectionLoader::load($selectedModuleNamespace . '\lang\\' . $defaultN2nLocale->getLanguageId());
					$data[$selectedModuleNamespace][$defaultN2nLocale->getId()] = $tc->getTexts();
				} else {
					$defaultIniData = $data[$selectedModuleNamespace][$defaultN2nLocale->getId()];
				}
				
				$data[$selectedModuleNamespace][$localeId] = $this->getTextData($defaultIniData, $localeId, $selectedModuleNamespace);
			}
		}
		
		if ($this->skipTranslated) {
			$defaultData = [];
			foreach ($data[$defaultN2nLocale->getId()] as $moduleNs => $defaultData) {
				$defaultData[$moduleNs] = [];
				foreach ($defaultData as $key => $value) {
					$useKey = false;
					foreach ($this->selectedLocaleIds as $localeId) {
						if (!isset($data[$localeId][$moduleNs])) continue;
						$useKey = true;
						break;
					}
					
					if (!$useKey) continue;
					
					$defaultData[$moduleNs][$key] = $value;
				}
			}
			
			$data[$defaultN2nLocale->getId()] = $defaultData;
		}
		
		$this->data = $data;
	}
	
	public function prepareData(array $moduleData) {
		$preparedData = [];
		$i = 1;
		foreach ($moduleData as $id => $textBlocks) {
			foreach ($textBlocks as $key => $value) {
				if (!isset($preparedData[$key][0])) {
					$preparedData[$key][0] = $key;
				}
				$preparedData[$key][$i] = $value;
			}
			$i++;
		}
		return $preparedData;
	}
	
	private function getTextblocks(N2nLocale $locale, $moduleNs) {
		$tc = TextCollectionLoader::loadIfExists($moduleNs . '\lang\\' . $locale->getId());
		if (!$tc) {
			$tc = TextCollectionLoader::loadIfExists($moduleNs . '\lang\\' . $locale->getLanguageId());
		}
		if (!$tc) {
			return [];
		}
		return $tc->getTexts();
	}
	
	private function getTextData(array $defaultData, string $localeId, string $moduleNs) {
		$locale = N2nLocale::build($localeId);
		$data = $this->getTextblocks($locale, $moduleNs);
		
		foreach ($defaultData as $key => $value) {
			if (isset($data[$key])) {
				if ($this->skipTranslated) {
					continue;
				}
				$value = $defaultData[$key];
			}
			$data[$key] = $value;
		}
		
		return $data;
	}
	
	public function getModuleNamespaceOptions() {
		$moduleNamespaces = array();
// 		$defaultN2nLocale = N2nLocale::getDefault();
		
		foreach ($this->moduleManager->getModules() as $module) {
			if (self::isModuleSealed((string) $module)) continue;
			
			$dtc = new DynamicTextCollection($module, N2N::getN2nLocales());
			if ($dtc->isEmpty()) continue;
			$moduleNamespaces[$module->getNamespace()] = $module->getModuleInfo()->getName();
		}
		return $moduleNamespaces;
	}
	
	public static function isModuleSealed(string $namespace) {
		if ($namespace === N2N::NS) return true;
		
		foreach (TypeLoader::getNamespaceDirPaths($namespace) as $dirPath) {
			if (preg_match('/.*\\\\(vendor|lib)\\\\.*/', realpath($dirPath)) > 0) return true;
		}
		
		foreach (TypeLoader::getNamespaceDirPaths($namespace . '\\') as $dirPath) {
			if (preg_match('/.*\\\\(vendor|lib)\\\\.*/', realpath($dirPath)) > 0) return true;
		}
		
		return false;
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
	
}
