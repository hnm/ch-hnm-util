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
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\meta\structure\Table;
use n2n\impl\persistence\orm\property\relation\RelationFactory;
use n2n\persistence\orm\property\EntityProperty;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\util\type\CastUtils;
use n2n\impl\persistence\orm\property\relation\JoinTableRelation;
use n2n\impl\persistence\orm\property\relation\InverseJoinColumnOneToManyRelation;
use n2n\reflection\annotation\AnnotationSet;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\annotation\AnnoJoinColumn;
use n2n\persistence\meta\structure\IndexType;
use n2n\persistence\orm\annotation\AnnoJoinTable;
use hangar\api\ColumnDefaults;
use hangar\api\CompatibilityLevel;
use phpbob\PhpbobUtils;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;

class OneToManyPropDef implements HangarPropDef {
	private $columnDefaults;
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
		return 'OneToMany';
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 * @return MagCollection
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new OrmRelationMagCollection($this->huoContext->getEntityModelManager(), true, true);
		
		if (null !== $propSourceDef) {
			if ($propSourceDef->hasPhpPropertyAnno(AnnoOneToMany::class)) {
				$phpAnnotation = $propSourceDef->getPhpPropertyAnno(AnnoOneToMany::class);
				
				$localName = OrmRelationMagCollection::determineLocalName($phpAnnotation->getPhpAnnoParam(1));
				$magCollection->setTargetEntityClasName($propSourceDef->determineTypeName($localName));
				
				if ($phpAnnotation->hasPhpAnnoParam(2)) {
					$magCollection->setMappedBy($phpAnnotation->getPhpAnnoParam(2)->getStringValue());
				}
				
				if ($phpAnnotation->hasPhpAnnoParam(3)) {
					$magCollection->setCascadeTypes(
							OrmRelationMagCollection::determineCascadeTypes($phpAnnotation->getPhpAnnoParam(3)));
				}
				
				if ($phpAnnotation->hasPhpAnnoParam(4)) {
					$magCollection->setFetchType(
							OrmRelationMagCollection::determineFetchType($phpAnnotation->getPhpAnnoParam(4)));
				}
				
				if ($phpAnnotation->hasPhpAnnoParam(5)) {
					$magCollection->setOrphanRemoval($phpAnnotation->getPhpAnnoParam(5)->getBoolValue());
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
		$propSourceDef->setArrayLikePhpTypeDef(null);
		
		if ($propSourceDef->hasPhpPropertyAnno(AnnoOneToMany::class)) {
			$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoOneToMany::class);
			$localName = OrmRelationMagCollection::determineLocalName($phpAnno->getPhpAnnoParam(1));
			$typeName = $propSourceDef->determineTypeName($localName);
			if (null === $typeName && null !== ($phpTypeDef = $propSourceDef->getArrayLikePhpTypeDef())) {
				$typeName = $phpTypeDef->getTypeName();
			}
			
			$propSourceDef->removePhpUse($typeName);
			$propSourceDef->removePhpPropertyAnno(AnnoOneToMany::class);
			$propSourceDef->removePhpUse(AnnoOneToMany::class);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll($dataSet->toArray());
		
		$targetEntityTypeName = $dataSet->get(OrmRelationMagCollection::PROP_NAME_TARGET_ENTITY_CLASS);
		$propSourceDef->setArrayLikePhpTypeDef(PhpTypeDef::fromTypeName($targetEntityTypeName));
		
		$propSourceDef->setPhpTypeDef(null);
		
		$anno = $propSourceDef->getOrCreatePhpPropertyAnno(AnnoOneToMany::class);
		$anno->resetPhpAnnoParams();
		$anno->createPhpAnnoParam(PhpbobUtils::extractClassName($targetEntityTypeName) . '::getClass()');
		$propSourceDef->createPhpUse($targetEntityTypeName);
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$dataSet->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$dataSet->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));
		
		$orphanRemoval = ($dataSet->get(OrmRelationMagCollection::PROP_NAME_ORPHAN_REMOVAL));
		if (!$orphanRemoval) {
			$orphanRemoval = null;
		} else {
			$orphanRemoval = 'true';
		}
		
		if (null !== ($mappedBy = $dataSet->get(OrmRelationMagCollection::PROP_NAME_MAPPED_BY))) {
			$anno->createPhpAnnoParam($mappedBy, true);
		} else {
			if (null !== $cascadeTypeValue || null !== $fetchType || null !== $orphanRemoval) {
				$anno->createPhpAnnoParam('null');
			}
		}
		
		if (null !== $cascadeTypeValue) {
			$anno->createPhpAnnoParam($cascadeTypeValue);
		} else if (null !== $fetchType || null !== $orphanRemoval) {
			$anno->createPhpAnnoParam('null');
		}
		
		if (null !== $fetchType) {
			$anno->createPhpAnnoParam($fetchType);
		} elseif (null !== $orphanRemoval) {
			$anno->createPhpAnnoParam('null');
		}
	
		if (null !== $orphanRemoval) {
			$anno->createPhpAnnoParam($orphanRemoval);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof RelationEntityProperty);
		
		$propertyName = $propSourceDef->getPropertyName();
		$annoOneToMany = $annotationSet->getPropertyAnnotation($propertyName, AnnoOneToMany::class);
		CastUtils::assertTrue($annoOneToMany instanceof AnnoOneToMany);
		
		if (null === $annoOneToMany->getMappedBy()) {
			$idColumnSize = $this->columnDefaults->getDefaultIntegerSize();
			$idColumnSigned = $this->columnDefaults->getDefaultInterSigned();

			$joinMode = RelationFactory::detectJoinMode(RelationEntityProperty::TYPE_ONE_TO_MANY, 
					null !== $annotationSet->getPropertyAnnotation($propertyName, AnnoJoinColumn::class), 
					null !== $annotationSet->getPropertyAnnotation($propertyName, AnnoJoinTable::class));
			
			$relation = $entityProperty->getRelation();
			$database = $dbInfo->getDatabase();
			
			if ($joinMode == RelationFactory::JOIN_MODE_TABLE) {
				CastUtils::assertTrue($relation instanceof JoinTableRelation);

				$joinTable = null;
				$joinTableName = $relation->getJoinTableName();
				
				if ($database->containsMetaEntityName($joinTableName)) {
					$joinTable = $database->getMetaEntityByName($joinTableName);
					CastUtils::assertTrue($joinTable instanceof Table);
				} else {
					$joinTable = $database->createMetaEntityFactory()->createTable($joinTableName);
				}
				
				$columnFactory = $joinTable->createColumnFactory();
				$joinTable->removeAllColumns();
				
				$joinColumnName = $relation->getJoinColumnName();
				$inverseJoinColumnName = $relation->getInverseJoinColumnName();

				$columnFactory->createIntegerColumn($joinColumnName, $idColumnSize, $idColumnSigned);
				$columnFactory->createIntegerColumn($inverseJoinColumnName, $idColumnSize, $idColumnSigned);
				
				$joinTable->removeAllIndexes();
				$joinTable->createIndex(IndexType::PRIMARY, array($joinColumnName, $inverseJoinColumnName));
			} else {
				CastUtils::assertTrue($relation instanceof InverseJoinColumnOneToManyRelation);
				
				$targetTableName = $relation->getTargetEntityModel()->getTableName();
				$targetTable = null;

				if ($database->containsMetaEntityName($targetTableName)) {
					$targetTable = $database->getMetaEntityByName($targetTableName);
					CastUtils::assertTrue($targetTable instanceof Table);
				} else {
					$targetTable = $database->createMetaEntityFactory()->createTable($targetTableName);
				}
				
				$inverseJoinColumnName = $relation->getInverseJoinColumnName();
				if ($targetTable->containsColumnName($inverseJoinColumnName)) {
					$targetTable->removeColumnByName($inverseJoinColumnName);
				}
				
				$targetTable->createColumnFactory()->createIntegerColumn($inverseJoinColumnName, $idColumnSize, $idColumnSigned);
			}
		}
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
		$propertyAnnoCollection = $propSourceDef->getPhpProperty()->getPhpPropertyAnnoCollection();
		if ($propertyAnnoCollection->hasPhpAnno(AnnoOneToMany::class)) {
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
