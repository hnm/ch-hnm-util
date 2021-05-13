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
use hangar\api\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\persistence\orm\annotation\AnnoManyToMany;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\util\type\CastUtils;
use n2n\impl\persistence\orm\property\relation\JoinTableToManyRelation;
use n2n\persistence\meta\structure\IndexType;
use hangar\api\ColumnDefaults;
use hangar\api\CompatibilityLevel;
use phpbob\PhpbobUtils;
use phpbob\representation\PhpTypeDef;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;
use n2n\web\dispatch\mag\MagCollection;
use n2n\util\type\attrs\DataSet;

class ManyToManyPropDef implements HangarPropDef {
	protected $columnDefaults;
	private $huoContext;
	
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
		$this->huoContext = $huoContext;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'ManyToMany';
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 * @return MagCollection
	 */
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new OrmRelationMagCollection($this->huoContext->getEntityModelManager());
		
		if (null !== $propSourceDef) {
			if ($propSourceDef->hasPhpPropertyAnno(AnnoManyToMany::class)) {
				$phpAnnotation = $propSourceDef->getPhpPropertyAnno(AnnoManyToMany::class);
				
				$localName = OrmRelationMagCollection::determineLocalName($phpAnnotation->getPhpAnnoParam(1));
				$magCollection->setTargetEntityClasName($propSourceDef->determineTypeName($localName) ?? $localName);
				
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
		
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManyToMany::class)) {
			$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoManyToMany::class);
			
			$localName = OrmRelationMagCollection::determineLocalName($phpAnno->getPhpAnnoParam(1));
			$typeName = $propSourceDef->determineTypeName($localName);
			if (null === $typeName && null !== ($phpTypeDef = $propSourceDef->getArrayLikePhpTypeDef())) {
				$typeName = $phpTypeDef->getTypeName();
			}
			
			$propSourceDef->removePhpUse($typeName);
			$propSourceDef->removePhpPropertyAnno(AnnoManyToMany::class);
			$propSourceDef->removePhpUse(AnnoManyToMany::class);
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
		
		$anno = $propSourceDef->getOrCreatePhpPropertyAnno(AnnoManyToMany::class);
		$anno->resetPhpAnnoParams();
		$anno->createPhpAnnoParam(PhpbobUtils::extractClassName($targetEntityTypeName) . '::getClass()');
		$propSourceDef->createPhpUse($targetEntityTypeName);
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$dataSet->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$dataSet->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));
		
		if (null !== ($mappedBy = $dataSet->get(OrmRelationMagCollection::PROP_NAME_MAPPED_BY))) {
			$anno->createPhpAnnoParam($mappedBy, true);
		} else {
			if (null !== $cascadeTypeValue || null !== $fetchType) {
				$anno->createPhpAnnoParam('null');
			}
		}
		
		if (null !== $cascadeTypeValue) {
			$anno->createPhpAnnoParam($cascadeTypeValue);
		} else if (null !== $fetchType) {
			$anno->createPhpAnnoParam('null');
		}
		
		if (null !== $fetchType) {
			$anno->createPhpAnnoParam($fetchType);
		}
	}

	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, 
			EntityProperty $entityProperty, AnnotationSet $as) {
		
		ArgUtils::assertTrue($entityProperty instanceof RelationEntityProperty);
		
		$propertyName = $propSourceDef->getPropertyName();
		$annoManyToMany = $as->getPropertyAnnotation($propertyName, AnnoManyToMany::class);
		CastUtils::assertTrue($annoManyToMany instanceof AnnoManyToMany);
		
		if (null === $annoManyToMany->getMappedBy()) {
			$relation = $entityProperty->getRelation();
			if ($relation instanceof JoinTableToManyRelation) {
				$joinTableName = $relation->getJoinTableName();
				$joinColumnName = $relation->getJoinColumnName();
				$inverseJoinColumnName = $relation->getInverseJoinColumnName();
				
				$database = $dbInfo->getDatabase();
				if ($database->containsMetaEntityName($joinTableName)) {
					$database->removeMetaEntityByName($joinTableName);
				}
				
				$table = $database->createMetaEntityFactory()->createTable($joinTableName);
				$columnFactory = $table->createColumnFactory();
				//@todo id column defs from hangar
				$columnFactory->createIntegerColumn($joinColumnName, 
						$this->columnDefaults->getDefaultIntegerSize(), $this->columnDefaults->getDefaultInterSigned());
				$columnFactory->createIntegerColumn($inverseJoinColumnName, $this->columnDefaults->getDefaultIntegerSize(), 
						$this->columnDefaults->getDefaultInterSigned());
				$table->createIndex(IndexType::PRIMARY, array($joinColumnName, $inverseJoinColumnName));
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
		if ($propertyAnnoCollection->hasPhpAnno(AnnoManyToMany::class)) {
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