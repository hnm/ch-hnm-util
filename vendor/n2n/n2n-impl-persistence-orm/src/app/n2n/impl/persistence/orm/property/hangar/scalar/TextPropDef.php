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

use hangar\api\PropSourceDef;
use n2n\util\type\attrs\DataSet;
use hangar\api\DbInfo;
use n2n\impl\web\dispatch\mag\model\NumericMag;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\persistence\meta\structure\ColumnFactory;
use n2n\persistence\orm\property\EntityProperty;
use n2n\util\type\ArgUtils;
use n2n\persistence\meta\structure\common\CommonTextColumn;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use hangar\api\CompatibilityLevel;
use n2n\util\StringUtils;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;

class TextPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_SIZE = 'size';
	const PROP_NAME_CHARSET = 'charset';
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'Text';
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new MagCollection();
		
		$size = $this->columnDefaults->getDefaultTextSize();
		$charset = $this->columnDefaults->getDefaultTextCharset();
		
		if (null !== $propSourceDef) {
			$size = $propSourceDef->getHangarData()->get(self::PROP_NAME_SIZE, false, $size);
			$charset = $propSourceDef->getHangarData()->get(self::PROP_NAME_CHARSET, false, $charset);
		}
		$magCollection->addMag(self::PROP_NAME_SIZE, new NumericMag('Size', $size, true));
		$magCollection->addMag(self::PROP_NAME_CHARSET, new StringMag('Charset', $charset));
		
		return $magCollection;
	}

	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(array(self::PROP_NAME_SIZE => $dataSet->get(self::PROP_NAME_SIZE), 
				self::PROP_NAME_CHARSET => $dataSet->get(self::PROP_NAME_CHARSET, false)));
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('string'));
	}
	
	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): Column {
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
	
		return new CommonTextColumn($entityProperty->getColumnName(),
				$this->determineSize($propSourceDef->getHangarData()),
				$this->determineCharset($propSourceDef->getHangarData()));
	}

	/**
	 * @param EntityProperty $entityProperty
	 * @return int
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->getPhpTypeDef() === null || $propSourceDef->getPhpTypeDef()->isString()) {
			switch ($propSourceDef->getPropertyName()) {
				case 'description':
				case 'lead':
					return CompatibilityLevel::COMMON;
			}
			
			if (StringUtils::endsWith('Html', $propSourceDef->getPropertyName())) {
				return CompatibilityLevel::COMMON;
			}
			
			if (null !== $propSourceDef) {
				return CompatibilityLevel::COMPATIBLE;
			}
			
			return parent::testCompatibility($propSourceDef);
		}
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}

	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, ColumnFactory $columnFactory, $columnName, DataSet $attributes) {
		$columnFactory->createTextColumn($columnName, $this->determineSize($attributes), 
				$this->determineCharset($attributes));
	}
	
	private function determineSize(DataSet $dataSet) {
		return $dataSet->optInt(self::PROP_NAME_SIZE, $this->columnDefaults->getDefaultTextSize());
	}
	
	private function determineCharset(DataSet $dataSet) {
		return $dataSet->optString(self::PROP_NAME_CHARSET, $this->columnDefaults->getDefaultTextCharset());
	}
	
	public function isBasic(): bool {
		return false;
	}
}
