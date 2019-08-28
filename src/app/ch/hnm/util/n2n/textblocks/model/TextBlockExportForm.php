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
		$spreadSheet = new Spreadsheet();
		$spreadSheet->setActiveSheetIndex(0)->fromArray($data, null, 'A1', true);
		$writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
		$tmpPath = tempnam(sys_get_temp_dir(), 'event');
		$tmpFilePath = new FsPath(tempnam(sys_get_temp_dir(), 'event'));
		$writer->save((string) $tmpFilePath);
		return new CommonFile(new FsFileSource($tmpFilePath), $fileName);
		
		$data = [$defaultN2nLocale => []];
		foreach ($this->selectedLocaleIds as $localeId) {
			$data[$localeId] = [];
			foreach ($this->selectedModuleNamespaces as $selectedModuleNamespace) {
				if (null === ($iniFilePath = $this->determineIniFilePath($selectedModuleNamespace, $defaultN2nLocale))) continue;
				
				$defaultIniData = [];
				if (!isset($data[$defaultN2nLocale][$selectedModuleNamespace])) {
					$defaultIniData = IoUtils::parseIniFile($path);
					$data[$defaultN2nLocale][$selectedModuleNamespace] = $defaultIniData;
				} else {
					$defaultIniData = $data[$defaultN2nLocale][$selectedModuleNamespace];
				}
				
				$data[$localeId][$selectedModuleNamespace] = $this->getData($defaultIniData, $localeId, $selectedModuleNamespace);
			}
		}
		
		if ($this->skipTranslated) {
			$defaultData = [];
			foreach ($data[$defaultN2nLocale] as $moduleNs => $defaultData) {
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
			
			$data[$defaultN2nLocale] = $defaultData;
		}
	}
	
	private function getData(array $defaultIniData, string $localeId, string $moduleNs) {
		$localeIniData = [];
		try {
			$localeIniFilePath = $this->determineIniFilePath($selectedModuleNamespace, $localeId);
			$localeIniData = IoUtils::parseIniFile($localeIniFilePath);
		} catch (TypeNotFoundException $e) {}
		
		$data = [];
		foreach ($defaultIniData as $key => $value) {
			if (isset($localeIniData[$key])) {
				if ($this->skipTranslated) {
					continue;
				}
				$value = $localeParsedIniString[$key];
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