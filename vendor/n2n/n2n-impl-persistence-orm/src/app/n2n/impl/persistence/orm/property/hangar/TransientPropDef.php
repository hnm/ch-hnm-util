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
use hangar\api\ColumnDefaults;
use hangar\api\CompatibilityLevel;
use phpbob\representation\PhpTypeDef;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;
use n2n\persistence\orm\annotation\AnnoTransient;
use n2n\impl\web\dispatch\mag\model\StringMag;

class TransientPropDef implements HangarPropDef {
	const PROP_NAME_TYPE_NAME = 'type_name';
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
	}
	
	public function getName(): string {
		return 'Transient';
	}
	
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new MagCollection();
		
		$typeName = null;
		if (null !== $propSourceDef && $propSourceDef->hasPhpTypeDef()) {
			$typeName = $propSourceDef->getPhpTypeDef()->getTypeName();
		}
		
		$magCollection->addMag(self::PROP_NAME_TYPE_NAME, new StringMag('Type Name', $typeName));
		$magCollection->getMagWrapperByPropertyName(self::PROP_NAME_TYPE_NAME);
		
		return $magCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
		$propSourceDef->removePhpPropertyAnno(AnnoTransient::class);
		$propSourceDef->removePhpUse(AnnoTransient::class);
	}
	
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		if (!empty($typeName = $dataSet->optString(self::PROP_NAME_TYPE_NAME))) {
			$propSourceDef->setPhpTypeDef(PhpTypeDef::fromTypeName($typeName));
		}
		
		$propSourceDef->getPhpProperty()->getPhpPropertyAnnoCollection()->getOrCreatePhpAnno(AnnoTransient::class);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::testCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoTransient::class)) return CompatibilityLevel::COMMON;
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::isBasic()
	 */
	public function isBasic(): bool {
		return false;
	}
}
