<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\persistence\orm\property\hangar\scalar;

use n2n\util\type\attrs\DataSet;
use hangar\api\DbInfo;
use n2n\persistence\meta\structure\ColumnFactory;
use hangar\api\PropSourceDef;
use n2n\impl\web\dispatch\mag\model\NumericMag;
use n2n\persistence\orm\property\EntityProperty;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use hangar\api\CompatibilityLevel;
use n2n\util\type\ArgUtils;
use n2n\persistence\meta\structure\common\CommonFixedPointColumn;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;

class FixedPointPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_NUM_INTEGER_DIGITS = 'num-integer-digits';
	const PROP_NAME_NUM_DECIMAL_DIGITS = 'num-decimal-digits';
	
	public function getName(): string {
		return 'Fixed Point';
	}

	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, ColumnFactory $columnFactory, $columnName, 
			DataSet $attributes) {
		$columnFactory->createFixedPointColumn($columnName, 
				$this->determineNumIntegerDigits($entityProperty->getName(), $attributes),
				$this->determineNumDecimalDigits($entityProperty->getName(), $attributes));
	}
	

	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$optionCollection = new MagCollection();
	
		$numIntegerDigits = $this->columnDefaults->getDefaultFixedPointNumIntegerDigits();
		$numDecimalDigits = $this->columnDefaults->getDefaultFixedPointNumDecimalDigits();
		if (null !== $propSourceDef) {
			$numIntegerDigits = $this->determineNumIntegerDigits($propSourceDef->getPropertyName(), 
					$propSourceDef->getHangarData());
			$numDecimalDigits = $this->determineNumDecimalDigits($propSourceDef->getPropertyName(), 
					$propSourceDef->getHangarData());		
		}
	
		$optionCollection->addMag(self::PROP_NAME_NUM_INTEGER_DIGITS, 
				new NumericMag('Num Integer Digits', $numIntegerDigits, true));
		
		$optionCollection->addMag(self::PROP_NAME_NUM_DECIMAL_DIGITS, 
				new NumericMag('Num Decimal Digits', $numDecimalDigits, true));
	
		return $optionCollection;
	}
	
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(
				array(self::PROP_NAME_NUM_DECIMAL_DIGITS => $dataSet->get(self::PROP_NAME_NUM_DECIMAL_DIGITS),
						self::PROP_NAME_NUM_INTEGER_DIGITS => $dataSet->get(self::PROP_NAME_NUM_INTEGER_DIGITS)));
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('float'));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\impl\persistence\orm\property\hangar\scalar\ScalarPropDefAdapter::testSourceCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		$phpTypeDef = $propSourceDef->getPhpTypeDef();
		if ($phpTypeDef === null || $phpTypeDef->isFloat()) {
			if (null !== $phpTypeDef) {
				return 2;
			}
				
			switch ($propSourceDef->getPropertyName()) {
				case 'lat':
				case 'lng':
				case 'currency':
					return CompatibilityLevel::COMMON;
			}
			
			return parent::testCompatibility($propSourceDef);
		}
	
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
	
	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
	
		return new CommonFixedPointColumn($entityProperty->getColumnName(), 
				$this->determineNumIntegerDigits($entityProperty->getName(), $propSourceDef->getHangarData()),
				$this->determineNumDecimalDigits($entityProperty->getName(), $propSourceDef->getHangarData()));
	}
	
	private function determineNumIntegerDigits($propertyName, DataSet $dataSet) {
		if ($dataSet->contains(self::PROP_NAME_NUM_INTEGER_DIGITS)) {
			return $dataSet->reqInt(self::PROP_NAME_NUM_INTEGER_DIGITS);
		}
		
		switch ($propertyName) {
			case 'lat':
			case 'lng':
				return 3;
			case 'currency':
				return 15;
			default:
				return $this->columnDefaults->getDefaultFixedPointNumIntegerDigits();
		}
	}
	
	private function determineNumDecimalDigits($propertyName, DataSet $dataSet) {
		if ($dataSet->contains(self::PROP_NAME_NUM_DECIMAL_DIGITS)) {
			return $dataSet->reqInt(self::PROP_NAME_NUM_DECIMAL_DIGITS);
		}
		
		switch ($propertyName) {
			case 'lat':
			case 'lng':
				return 12;
			case 'currency':
				return 2;
			default:
				return $this->columnDefaults->getDefaultFixedPointNumDecimalDigits();
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::isBasic()
	 */
	public function isBasic(): bool {
		return true;
	}
}
