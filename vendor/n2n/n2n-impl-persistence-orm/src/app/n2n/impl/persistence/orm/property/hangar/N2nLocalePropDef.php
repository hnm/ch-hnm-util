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

use hangar\api\HangarPropDef;
use hangar\api\PropSourceDef;
use n2n\util\type\attrs\DataSet;
use n2n\web\dispatch\mag\MagCollection;
use hangar\api\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\impl\persistence\orm\property\N2nLocaleEntityProperty;
use n2n\l10n\N2nLocale;
use n2n\util\type\ArgUtils;
use hangar\api\ColumnDefaults;
use n2n\persistence\meta\structure\common\CommonStringColumn;
use hangar\api\CompatibilityLevel;
use phpbob\representation\PhpTypeDef;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;
use n2n\persistence\orm\annotation\AnnoN2nLocale;

class N2nLocalePropDef implements HangarPropDef {
	const DEFAULT_LOCALE_COLUMN_LENGTH = '12';
	
	protected $columnDefaults;
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getName(): string {
		return 'N2nLocale';
	}
	
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		return new MagCollection();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
	    $propSourceDef->removePhpPropertyAnno(AnnoN2nLocale::class);
	    $propSourceDef->removePhpUse(AnnoN2nLocale::class);
	    $propSourceDef->removePhpUse(N2nLocale::class);
	}
	
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->setPhpTypeDef(PhpTypeDef::fromTypeName(N2nLocale::class));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof N2nLocaleEntityProperty);
		$columnName = $entityProperty->getColumnName();
		$dbInfo->removeColumn($columnName);
		
		$dbInfo->getTable()->createColumnFactory()
				->createStringColumn($columnName, self::DEFAULT_LOCALE_COLUMN_LENGTH);
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		ArgUtils::assertTrue($entityProperty instanceof N2nLocaleEntityProperty);
		return new CommonStringColumn($entityProperty->getColumnName(), self::DEFAULT_LOCALE_COLUMN_LENGTH);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::testCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoN2nLocale::class)) return CompatibilityLevel::COMMON;
		if (null !== ($phpTypeDef = $propSourceDef->getPhpTypeDef()) && 
				$phpTypeDef->getTypeName() === N2nLocale::class) return CompatibilityLevel::COMMON;
		
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
