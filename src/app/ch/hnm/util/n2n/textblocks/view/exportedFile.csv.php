<?php
	use n2n\io\IoUtils;
	use n2n\impl\web\ui\view\csv\CsvView;
	
	$view = CsvView::view($view);
	$csv = CsvView::csv($view);
	
	$iniFilePath = $view->getParam('iniFilePath');
	
	$parsedIniString = IoUtils::parseIniFile($iniFilePath);
	$localeParsedIniString = array();
	if (null !== ($localeIniFilePath = $view->getParam('localeIniFilePath'))) {
		$localeParsedIniString = IoUtils::parseIniFile($localeIniFilePath);
	}
	
	$skipTranslated = $view->getParam('skipTranslated', false, false);
	
	$content = array();
	$csv->meta()->setCellDelimiter(';');
	foreach ($parsedIniString as $key => $value) {
		if (isset($localeParsedIniString[$key])) {
			if ($skipTranslated) continue;
			$value = $localeParsedIniString[$key];
		}
		$content[] = array($key, str_replace('"', '', $value));
	}
?>
<?php $csv->encode($content, false) ?>