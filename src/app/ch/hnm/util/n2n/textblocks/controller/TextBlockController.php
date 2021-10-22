<?php
namespace ch\hnm\util\n2n\textblocks\controller;

use ch\hnm\util\n2n\textblocks\model\TextBlockExportForm;
use n2n\web\http\controller\ControllerAdapter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use n2n\io\fs\FsPath;
use n2n\io\managed\impl\CommonFile;
use n2n\io\managed\impl\FsFileSource;

class TextBlockController extends ControllerAdapter {
	
	public function index(TextBlockExportForm $textBlockExportForm) {
		
		if ($this->dispatch($textBlockExportForm, 'export')) {
			$spreadSheet = new Spreadsheet();
			$i = 0;
			foreach ($textBlockExportForm->getData() as $moduleNs => $moduleData) {
				if ($i > 0) {
					$spreadSheet->createSheet($i);
				}
				$spreadSheet->setActiveSheetIndex($i);
				$spreadSheet->getActiveSheet()->setTitle($moduleNs);
				$preparedData = $textBlockExportForm->prepareData($moduleData);
				$spreadSheet->setActiveSheetIndex($i)->fromArray($preparedData, null, 'A1', true);
				$i++;
			}
			
			$writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
			$tmpPath = tempnam(sys_get_temp_dir(), 'event');
			$tmpFilePath = new FsPath(tempnam(sys_get_temp_dir(), 'event'));
			$writer->save((string) $tmpFilePath);
			$file = new CommonFile(new FsFileSource($tmpFilePath), 'translations.xlsx');
			$this->sendFile($file);
		}
		
		$this->forward('\ch\hnm\util\n2n\textblocks\view\export.html', 
				array('textBlockExportForm' => $textBlockExportForm));
	}
}
