<?php
namespace ch\hnm\util\rocket\import\bo;

use ch\hnm\util\rocket\import\model\Csv;

class Import {
	private $csv;
	private $scalarEiProperties;
	
	public function __construct(Csv $csv, array $scalarEiProperties) {
		$this->csv = $csv;
		$this->scalarEiProperties = $scalarEiProperties;
	}
	
	public function getCsv() {
		return $this->csv;
	}
	
	public function setCsv(Csv $csv) {
		$this->csv = $csv;
	}

    public function getScalarEiProperties() {
        return $this->scalarEiProperties;
    }

    public function setScalarEiProperties($scalarEiProperties) {
        $this->scalarEiProperties = $scalarEiProperties;
    }

}