<?php
namespace ch\hnm\util\rocket\import\bo;

use ch\hnm\util\rocket\import\model\CsvException;

class CsvLine {
	private $entityIdRep;
	private $values;
	private $num;

	public static function create(string $line, $num, ?array $colNames = null) {
		$values = array();
		$cells = explode(',', $line);

		if ($colNames === null) {
			$colNames = $cells;
		}

		if (count($colNames) < count($values)) {
			throw new CsvException("hello");
		}

		while (null != ($colName = array_shift($colNames))) {
			$values[$colName] = utf8_encode(array_shift($cells));
		}

		return new CsvLine($values, $num);
	}

	public function __construct(array $values, string $num) {
		$this->values = $values;
		$this->num = $num;
	}

	public function getEntityIdRep() {
		return $this->entityIdRep;
	}

	public function setEntityIdRep($entityIdRep) {
		$this->entityIdRep = $entityIdRep;
	}

	public function getValues() {
		return $this->values;
	}

	public function setValues(array $values) {
		$this->values = $values;
	}

	public function getNum() {
		return $this->num;
	}

	public function setNum(string $num)	{
		$this->num = $num;
	}
}