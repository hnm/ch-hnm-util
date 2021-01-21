<?php
namespace ch\hnm\util\n2n\textblocks\model;

use n2n\web\dispatch\Dispatchable;
use n2n\core\TypeLoader;
use n2n\core\N2N;
use n2n\core\TypeNotFoundException;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
		foreach ($this->selectedModuleNamespaces as $selectedModuleNamespace) {
			foreach ($this->selectedLocaleIds as $localeId) {
				$data[$selectedModuleNamespace][$localeId] = $this->getData($localeId, $selectedModuleNamespace);
			}
		}
		

		$languageFiles = [];
		foreach ($data as $module => $data) {
			$i = 1;
			$header = ['key'];
			foreach ($data as $language => $translation) {
				$header[] = $language;
				foreach ($translation as $key => $value) {
					$languageFiles[$module][$key][0] = $key;
					$languageFiles[$module][$key][$i] = $value;
				}
				$i++;
			}
			
			if ($this->skipTranslated) {
				$headerCount = count($header);
				foreach ($languageFiles[$module] as $key => $translation) {
					$translatedKeys = 0;
					foreach ($translation as $value) {
						if (!empty($value)) $translatedKeys++;
					}
					if ($headerCount == $translatedKeys) {
						unset($languageFiles[$module][$key]);
					}
				}
			}
			array_unshift($languageFiles[$module], $header);
		
			$spreadSheet = new Spreadsheet();
			$spreadSheet->setActiveSheetIndex(0)->fromArray($languageFiles[$module], null, 'A1', true);
			$writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
			$fileFsPath = $this->n2nContext->getVarStore()->requestFileFsPath(VarStore::CATEGORY_TMP,
					'tmpl', 'textblocks', str_replace('\\', '.', $module) . '.xlsx', true, true);
			$writer->save((string) $fileFsPath);
			$mc->addInfo('"' . (string) $fileFsPath . '" erstellt.');
			
		}
		
	}
	
	private function getData(string $localeId, string $moduleNs) {
		$localeIniData = [];
		try {
			$localeIniFilePath = $this->determineIniFilePath($moduleNs, $localeId);
			$localeIniData = IoUtils::parseIniFile($localeIniFilePath);
		} catch (TypeNotFoundException $e) {}
		
		return $localeIniData;
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
			$localeIdOptions[$locale->getLanguageId()] = $locale->getLanguage();
		}
		return $localeIdOptions;
	}
	
	private function determineIniFilePath($moduleNamespace, $localeId) {
		$n2nLocale = N2nLocale::create($localeId);
		
		try {
			return TypeLoader::getFilePathOfType($moduleNamespace . '\\' . DynamicTextCollection::LANG_NS_EXT
					. '\\' . $n2nLocale, TextCollectionLoader::LANG_INI_FILE_SUFFIX);
		} catch (TypeNotFoundException $e) {
			if ($n2nLocale->getLanguageId() === null) return null;
		}
		
		try {
			return TypeLoader::getFilePathOfType($moduleNamespace . '\\' . DynamicTextCollection::LANG_NS_EXT
					. '\\' . $n2nLocale->getLanguageId(), TextCollectionLoader::LANG_INI_FILE_SUFFIX);
		} catch (TypeNotFoundException $e) {
			return null;
		}
	}
}
