<?php
namespace ch\hnm\util\rocket\import\model;

class Csv {
	
	private $csvStr;
	private $rows = array();
	private $columnNames = array();
	
	public function __construct(string $csvString) {
		$this->csvStr = $csvString;
		
		$lines  = preg_split("/\\r\\n|\\r|\\n/", $this->csvStr);
		
		$this->columnNames = explode(',', array_shift($lines));
		$this->rows = $this->buildRows($lines);
	}
	
	public function getColumnNames() {
		return $this->columnNames;
	}
	
	public function getRows() {
		return $this->rows;
	}
	
	private function buildRows($lines) {
		$dataArr = array();
		
		foreach ($lines as $line) {
			$dataArr[] = explode(',', $line);
		}

		return $dataArr;
	}
}