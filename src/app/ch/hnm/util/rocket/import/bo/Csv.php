<?php
namespace ch\hnm\util\rocket\import\bo;

use n2n\util\StringUtils;

class Csv {
	private $csvStr;
	private $csvLines = array();
	private $columnNames = array();
	
	public function __construct(string $csvString) {
		$this->csvStr = $csvString;

		$lineNum = 0;

		$lines  = preg_split("/\\r\\n|\\r|\\n/", $this->csvStr);

		$this->columnNames = CsvLine::create(array_shift($lines), ++$lineNum, null)->getValues();
		foreach ($lines as $line) {
			$this->csvLines[] = CsvLine::create($line, ++$lineNum, $this->columnNames);
		}
	}

	public function buildStateJson(string $state) {
		$stateJsonArr = array();

		$stateJsonArr['state'] = $state;

		foreach ($this->csvLines as $cl) {
			$stateJsonArr['uploaded'][] = array('lineNum' => $cl->getNum(), 'idRep' => $cl->getEntityIdRep());
		}

		return StringUtils::jsonEncode($stateJsonArr);
	}

	public function getColumnNames() {
		return $this->columnNames;
	}
	
	public function getCsvLines() {
		return $this->csvLines;
	}
}