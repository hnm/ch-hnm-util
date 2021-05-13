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
use n2n\persistence\meta\structure\ColumnFactory;
use hangar\api\DbInfo;
use hangar\api\PropSourceDef;
use n2n\persistence\orm\property\EntityProperty;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use n2n\persistence\meta\structure\common\CommonIntegerColumn;
use hangar\api\CompatibilityLevel;
use phpbob\representation\PhpTypeDef;
use n2n\impl\persistence\orm\property\BoolEntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;
use n2n\impl\web\dispatch\mag\model\EnumMag;

class BooleanPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_DEFAULT_VALUE = 'defaultValue';
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'Boolean';
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 * @return MagCollection
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new MagCollection();
		$magCollection->addMag(self::PROP_NAME_DEFAULT_VALUE, 
				new EnumMag('Default Value', [null => 'No Default', 'true' => 'true', 'false' => 'false'], 
						(null !== $propSourceDef) ? $propSourceDef->getPhpProperty()->getValue() : null));
		return $magCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('bool'));
		if (null !== ($defaultValue = $dataSet->optString(self::PROP_NAME_DEFAULT_VALUE))) {
			$propSourceDef->getPhpProperty()->setValue($defaultValue);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\impl\persistence\orm\property\hangar\scalar\ScalarPropDefAdapter::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty,
			AnnotationSet $annotationSet) {
				
		ArgUtils::assertTrue($entityProperty instanceof BoolEntityProperty || $entityProperty instanceof ScalarEntityProperty);
		
		$columnName = $entityProperty->getColumnName();
		$dbInfo->removeColumn($columnName);
		$columnFactory = $dbInfo->getTable()->createColumnFactory();
		$attributes = $propSourceDef->getHangarData();
		
		$this->createColumn($entityProperty, $dbInfo, $columnFactory, $columnName, $attributes);
	}
	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): Column {
		ArgUtils::assertTrue($entityProperty instanceof BoolEntityProperty || $entityProperty instanceof ScalarEntityProperty);
	
		return new CommonIntegerColumn($entityProperty->getColumnName(), 1, false);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\impl\persistence\orm\property\hangar\scalar\ScalarPropDefAdapter::testSourceCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if (null === $propSourceDef->getPhpTypeDef() || $propSourceDef->getPhpTypeDef()->isBool()) {
			if (null !== $propSourceDef->getPhpTypeDef() || $propSourceDef->getPropertyName() === 'online') {
				return CompatibilityLevel::COMMON;
			}
			
			return parent::testCompatibility($propSourceDef);
		}
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
	
	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, 
			ColumnFactory $columnFactory, $columnName, DataSet $attributes) {
		$columnFactory->createIntegerColumn($columnName, 1, false);
	}
	
	public function isBasic(): bool {
		return true;
	}
}
