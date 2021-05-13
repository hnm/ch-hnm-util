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
namespace n2n\impl\persistence\orm\property\hangar;

use n2n\util\type\attrs\DataSet;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\DateTimeEntityProperty;
use n2n\impl\persistence\meta\mysql\MysqlDateTimeColumn;
use hangar\api\HangarPropDef;
use hangar\api\ColumnDefaults;
use hangar\api\PropSourceDef;
use hangar\api\DbInfo;
use hangar\api\CompatibilityLevel;
use phpbob\representation\PhpTypeDef;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\orm\annotation\AnnoDateTime;

class DateTimePropDef implements HangarPropDef {
	
	protected $columnDefaults;
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'DateTime';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		return new MagCollection();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
		$propSourceDef->removePhpPropertyAnno(AnnoDateTime::class);
		$propSourceDef->removePhpUse(AnnoDateTime::class);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('\DateTime'));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof DateTimeEntityProperty);
		$columnName = $entityProperty->getColumnName();
		$dbInfo->removeColumn($columnName);
		$dbInfo->getTable()->createColumnFactory()->createDateTimeColumn($columnName, true, true);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMetaColumn()
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		ArgUtils::assertTrue($entityProperty instanceof DateTimeEntityProperty);
		
		return new MysqlDateTimeColumn($entityProperty->getColumnName(), true, true);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::testCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoDateTime::class)) return CompatibilityLevel::COMMON;
		if (null !== ($phpTypeDef = $propSourceDef->getPhpTypeDef()) && 
				$phpTypeDef->getLocalName() === '\DateTime') return CompatibilityLevel::COMMON; 
		
		return CompatibilityLevel::NOT_COMPATIBLE; 
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::isBasic()
	 */
	public function isBasic(): bool {
		return true;
	}
}