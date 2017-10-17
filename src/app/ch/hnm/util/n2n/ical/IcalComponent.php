<?php
namespace ch\hnm\util\n2n\ical;

use n2n\web\ui\UiComponent;
use n2n\web\ui\BuildContext;

abstract class IcalComponent implements UiComponent {
	const KEY_BEGIN = 'BEGIN';
	const KEY_END = 'END';
	
	const KEY_VALUE_SEPARATOR = ':';
	
	public function getContents(): string {
		$contents = '';
		foreach ($this->getProperties() as $key => $value) {
			if (empty($key) || empty($value)) continue;
			$contents .= $key . self::KEY_VALUE_SEPARATOR . $value . N2N_CRLF;
		}
		return $contents;
	}
	
	public function build(BuildContext $buildContext): string {
		return $this->getContents();
	}
	
	public abstract function getProperties();
	
	public function __toString() {
		return $this->getContents();
	}
}