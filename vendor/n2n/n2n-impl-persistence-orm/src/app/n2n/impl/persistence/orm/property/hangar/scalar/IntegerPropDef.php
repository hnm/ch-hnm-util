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

use hangar\api\DbInfo;
use n2n\persistence\meta\structure\IndexType;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\persistence\meta\structure\ColumnFactory;
use n2n\util\type\attrs\DataSet;
use hangar\api\PropSourceDef;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\persistence\meta\structure\Size;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use n2n\persistence\orm\property\EntityProperty;
use hangar\api\CompatibilityLevel;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use n2n\util\type\ArgUtils;
use n2n\persistence\meta\structure\common\CommonIntegerColumn;
use phpbob\representation\PhpTypeDef;
use n2n\persistence\meta\structure\Column;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\persistence\orm\property\IntEntityProperty;

class IntegerPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_SIZE = 'size';
	const PROP_NAME_SIGNED = 'signed';
			
	public function getName(): string {
		return 'Integer';
	}

	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$optionCollection = new MagCollection();
	
		$size = $this->columnDefaults->getDefaultIntegerSize();
		$signed = $this->columnDefaults->getDefaultInterSigned();;
		
		if (null !== $propSourceDef) {
			$size = $propSourceDef->getHangarData()->get(self::PROP_NAME_SIZE, false, $size);
			$signed = $propSourceDef->getHangarData()->get(self::PROP_NAME_SIGNED, false, $signed);
		}
	
		$optionCollection->addMag(self::PROP_NAME_SIZE, new EnumMag('Size', 
				$this->getSizeOptions(), $size));
		$optionCollection->addMag(self::PROP_NAME_SIGNED, new BoolMag('Signed', $signed));
	
		return $optionCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(array(
				self::PROP_NAME_SIZE => $dataSet->get(self::PROP_NAME_SIZE),
				self::PROP_NAME_SIGNED => $dataSet->get(self::PROP_NAME_SIGNED)));
		
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('int'));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\impl\persistence\orm\property\hangar\scalar\ScalarPropDefAdapter::testCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if (null === $propSourceDef->getPhpTypeDef() || $propSourceDef->getPhpTypeDef()->isInt()) {
			if (null !== $propSourceDef->getPhpTypeDef()) {
				return CompatibilityLevel::COMMON;
			}
			
			switch ($propSourceDef->getPropertyName()) {
				case 'id':
				case 'orderIndex':
				case 'lft':
				case 'rgt':
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
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): Column {
		ArgUtils::assertTrue($entityProperty instanceof IntEntityProperty || $entityProperty instanceof ScalarEntityProperty);
	
		return new CommonIntegerColumn($entityProperty->getColumnName(),
				$this->determineSize($propSourceDef->getHangarData()),
				$this->determineSigned($propSourceDef->getHangarData()));
	}
	
	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, 
			ColumnFactory $columnFactory, $columnName, DataSet $attributes) {
		$columnFactory->createIntegerColumn($columnName, $this->determineSize($attributes),
				$this->determineSigned($attributes));
		
		if ($columnName == EntityModelFactory::DEFAULT_ID_PROPERTY_NAME) {
			$dbInfo->getTable()->createIndex(IndexType::PRIMARY, array($columnName));
		}
	}
	
	private function determineSize(DataSet $dataSet) {
		return $dataSet->get(self::PROP_NAME_SIZE, false, $this->columnDefaults->getDefaultIntegerSize());
	}
	
	private function determineSigned(DataSet $dataSet) {
		return $dataSet->get(self::PROP_NAME_SIGNED, false, $this->columnDefaults->getDefaultInterSigned());
	}
	
	private function getSizeOptions() {
		return array(Size::SHORT => 'Short', Size::MEDIUM => 'Medium', Size::INTEGER => 'Integer', 
				Size::LONG => 'Long');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::isBasic()
	 */
	public function isBasic(): bool {
		return true;
	}
}
