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
namespace n2n\impl\persistence\orm\property\hangar\relation;

use hangar\api\HangarPropDef;
use hangar\api\PropSourceDef;
use n2n\util\type\attrs\DataSet;
use hangar\api\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\impl\persistence\orm\property\ToOneEntityProperty;
use hangar\api\ColumnDefaults;
use n2n\impl\persistence\orm\property\relation\JoinColumnToOneRelation;
use n2n\persistence\meta\structure\IndexType;
use n2n\persistence\meta\structure\Table;
use hangar\api\CompatibilityLevel;
use phpbob\representation\PhpTypeDef;
use phpbob\PhpbobUtils;
use hangar\api\HuoContext;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;
use n2n\impl\persistence\orm\property\IntEntityProperty;

class ManyToOnePropDef implements HangarPropDef {
	protected $columnDefaults;
	private $huoContext;
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::setup()
	 */
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
		$this->huoContext = $huoContext;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'ManyToOne';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 * @return MagCollection
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new OrmRelationMagCollection($this->huoContext->getEntityModelManager(), false);
		
		if (null !== $propSourceDef) {
			if ($propSourceDef->hasPhpPropertyAnno(AnnoManyToOne::class)) {
				$phpAnnotation = $propSourceDef->getPhpPropertyAnno(AnnoManyToOne::class);
				
				$localName = OrmRelationMagCollection::determineLocalName($phpAnnotation->getPhpAnnoParam(1));
				$magCollection->setTargetEntityClasName($propSourceDef->determineTypeName($localName));

				if ($phpAnnotation->hasPhpAnnoParam(2)) {
					$magCollection->setCascadeTypes(
							OrmRelationMagCollection::determineCascadeTypes($phpAnnotation->getPhpAnnoParam(2)));
				}
				
				if ($phpAnnotation->hasPhpAnnoParam(3)) {
					$magCollection->setFetchType(
							OrmRelationMagCollection::determineFetchType($phpAnnotation->getPhpAnnoParam(3)));
				}
			}
		}
		
		return $magCollection;
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManyToOne::class)) {
			$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoManyToOne::class);
			$localName = OrmRelationMagCollection::determineLocalName($phpAnno->getPhpAnnoParam(1));
			$typeName = $propSourceDef->determineTypeName($localName);
			if (null === $typeName && null !== ($phpTypeDef = $propSourceDef->getPhpTypeDef())) {
				$typeName = $phpTypeDef->getTypeName();
			}
			
			$propSourceDef->removePhpUse($typeName);
			$propSourceDef->removePhpPropertyAnno(AnnoManyToOne::class);
			$propSourceDef->removePhpUse(AnnoManyToOne::class);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll($dataSet->toArray());
		
		$targetEntityTypeName = $dataSet->get(OrmRelationMagCollection::PROP_NAME_TARGET_ENTITY_CLASS);
		
		$propSourceDef->setPhpTypeDef(PhpTypeDef::fromTypeName($targetEntityTypeName));
		
		$anno = $propSourceDef->getOrCreatePhpPropertyAnno(AnnoManyToOne::class);
		$anno->resetPhpAnnoParams();
		$anno->createPhpAnnoParam(PhpbobUtils::extractClassName($targetEntityTypeName) . '::getClass()');
		$propSourceDef->createPhpUse($targetEntityTypeName);
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$dataSet->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$dataSet->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));

		// Pseudo mapped by
		if (null !== $cascadeTypeValue) {
			$anno->createPhpAnnoParam($cascadeTypeValue);
		} elseif (null !== $fetchType) {
			$anno->createPhpAnnoParam('null');
		}
		
		if (null !== $fetchType) {
			$anno->createPhpAnnoParam($fetchType);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof ToOneEntityProperty);
		
		$relation = $entityProperty->getRelation();
		ArgUtils::assertTrue($relation instanceof JoinColumnToOneRelation);
		
		$joinColumnName = $relation->getJoinColumnName();
		$table = $dbInfo->getTable();
		if (!$table->containsColumnName($joinColumnName)) {
			$relation->getTargetEntityModel()->getTableName();
			$targetEntityProperty = $relation->getTargetEntityModel()->getIdDef()->getEntityProperty();
			
			//@todo @andi: das sollte einfach sein eine lösung zu finden, sobald das die EntityProperties selbst können
			if ($targetEntityProperty instanceof IntEntityProperty || $targetEntityProperty->getName() === 'id') {
				$dbInfo->getTable()->createColumnFactory()->createIntegerColumn($joinColumnName, 
						$this->columnDefaults->getDefaultIntegerSize(), $this->columnDefaults->getDefaultInterSigned());
			} else {
				$dbInfo->getTable()->createColumnFactory()->createStringColumn($joinColumnName, 
						$this->columnDefaults->getDefaultStringLength(), $this->columnDefaults->getDefaultStringCharset());
			}
		}
		
		if (!$this->hasIndexForColumn($dbInfo->getTable(), $joinColumnName)) {
			$dbInfo->getTable()->createIndex(IndexType::INDEX, array($joinColumnName));
		}
	}
	
	private function hasIndexForColumn(Table $table, $columnName) {
		foreach ($table->getIndexes() as $index) {
			if ($index->containsColumnName($columnName) && count($index->getColumns()) === 1) return true;
		}
		
		return false;
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
	 * @see \hangar\api\HangarPropDef::testSourceCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManyToOne::class)) {
			return CompatibilityLevel::COMMON;
		}
		
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
