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
namespace n2n\impl\persistence\orm\property\relation;

use n2n\persistence\orm\property\ClassSetup;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\persistence\orm\annotation\OrmRelationAnnotation;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\OrmConfigurationException;
use n2n\persistence\orm\model\UnknownEntityPropertyException;
use n2n\impl\persistence\orm\property\relation\util\OrderDirective;
use n2n\persistence\orm\property\QueryItemRepresentableEntityProperty;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\annotation\AnnoAssociationOverrides;
use n2n\persistence\orm\FetchType;
use n2n\util\type\TypeUtils;

class RelationFactory {
	private $classSetup;
	private $relationProperty;
	private $relationAnnotation;
	private $annoJoinColumn;
	private $annoJoinTable;
	private $annoOrderBy;

	public function __construct(ClassSetup $classSetup, RelationEntityProperty $relationProperty,
			OrmRelationAnnotation $relationAnnotation) {
		$this->classSetup = $classSetup;
		$this->relationProperty = $relationProperty;
		$this->relationAnnotation = $relationAnnotation;
		
		$annotationSet = $classSetup->getAnnotationSet();
		
		$this->determineAssociations($classSetup, array($relationProperty->getName()));
		
		if ($this->annoJoinColumn === null && $this->annoJoinTable === null) {
			$this->annoJoinColumn = $annotationSet->getPropertyAnnotation(
					$relationProperty->getName(), 'n2n\persistence\orm\annotation\AnnoJoinColumn');
			
			$this->annoJoinTable = $annotationSet->getPropertyAnnotation(
					$relationProperty->getName(), 'n2n\persistence\orm\annotation\AnnoJoinTable');
		}

		if ($this->annoJoinColumn !== null && $this->annoJoinTable !== null) {
			throw $classSetup->createException('Conflicting annotations: JoinColumn and JoinTable'
							. ' defined for entity property' . $this->classSetup->getClass()->getName()
							. '::$' . $relationProperty->getName() . '.',
					null, array($this->annoJoinColumn, $this->annoJoinTable));
		}
		
		$this->annoOrderBy = $annotationSet->getPropertyAnnotation($relationProperty->getName(), 
				'n2n\persistence\orm\annotation\AnnoOrderBy');
	}
	
	private function determineAssociations(ClassSetup $classSetup, array $propertyNames) {
		$parentPropertyName = $classSetup->getParentPropertyName();
		
		$parentClassSetup = $classSetup->getParentClassSetup();
		if ($parentClassSetup === null) return;
		
		$newPropertyNames = $propertyNames;
		if ($parentPropertyName !== null) {
			$newPropertyNames[] = $parentPropertyName;
		}
		
		$this->determineAssociations($parentClassSetup, $newPropertyNames);
		
		if ($this->annoJoinColumn !== null && $this->annoJoinTable !== null) {
			return;
		}
		
		$parentAnnotationSet = $parentClassSetup->getAnnotationSet();
		
		$annoAssociationOverrides = null;
		if (null !== $parentPropertyName) {
			$annoAssociationOverrides = $parentAnnotationSet->getPropertyAnnotation($parentPropertyName, 
					'n2n\persistence\orm\annotation\AnnoAssociationOverrides');
		} else {
			$annoAssociationOverrides = $parentAnnotationSet->getClassAnnotation(
					'n2n\persistence\orm\annotation\AnnoAssociationOverrides');
		}
		
		if ($annoAssociationOverrides === null) return;

		$associationPropertyName = implode(self::PROPERTY_NAME_SEPARATOR, $propertyNames);
		ArgUtils::assertTrue($annoAssociationOverrides instanceof AnnoAssociationOverrides);

		$annoJoinColumns = $annoAssociationOverrides->getAnnoJoinColumns();
		if ($this->annoJoinColumn === null && isset($annoJoinColumns[$associationPropertyName])) {
			$this->annoJoinColumn = $annoJoinColumns[$associationPropertyName];
		}
		
		$annoJoinTables = $annoAssociationOverrides->getAnnoJoinTables();
		if ($this->annoJoinTable === null && isset($annoJoinTables[$associationPropertyName])) {
			$this->annoJoinTable = $annoJoinTables[$associationPropertyName];
		}
	}

	public function createMappedOneToOneRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_ONE_TO_ONE) {
			throw $this->createAssociationException('one-to-one', 'one-to-one');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAnnotations();
			
		$relation = new PropertyMappedOneToOneRelation($this->relationProperty, $targetEntityModel, 
				$targetEntityProperty);
		$this->completeRelation($relation);
		return $relation;
	}
	
	private function completeRelation(Relation $relation) {
		$relation->setCascadeType($this->relationAnnotation->getCascadeType());
		$relation->setFetchType($this->relationAnnotation->getFetchType());
		
		if ($this->relationAnnotation instanceof AnnoOneToMany 
				|| $this->relationAnnotation instanceof AnnoOneToOne) {
			$relation->setOrphanRemoval($this->relationAnnotation->isOrphanRemoval());
		}
	}

	public function createMappedOneToManyRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_MANY_TO_ONE) {
			throw $this->createAssociationException('one-to-many', 'many-to-one');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAnnotations();
		
		$relation = new PropertyMappedToManyRelation($this->relationProperty, $targetEntityModel, $targetEntityProperty);
		$this->completeRelation($relation);
		$relation->setOrderDirectives($this->determineOrderDirectives($targetEntityModel));
		return $relation;
	}

	public function createMappedManyToManyRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_MANY_TO_MANY) {
			throw $this->createAssociationException('many-to-many', 'many-to-many');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAnnotations();

		$relation = new PropertyMappedToManyRelation($this->relationProperty, $targetEntityModel, 
				$targetEntityProperty);
		$relation->setOrderDirectives($this->determineOrderDirectives($targetEntityModel));
		$this->completeRelation($relation);
		return $relation;
	}

	private function rejectJoinAnnotations() {
		if ($this->annoJoinColumn !== null) {
			throw $this->classSetup->createException('Join column annotated to mapped property:'
					. $this->relationProperty->toPropertyString(),
					null, array($this->annoJoinColumn));
		}

		if ($this->annoJoinTable !== null) {
			throw $this->classSetup->createException('Join table annotated to mapped property:'
					. $this->relationProperty->toPropertyString(),
					null, array($this->annoJoinTable));
		}
	}

	private function createAssociationException($typeName, $targetTypeName) {
		throw $this->classSetup->createException('Illegal attempt to associate ' . $typeName . ' '
				. $this->relationProperty->toPropertyString() . ' with non-' . $targetTypeName
				. ' property.', null, array($this->relationAnnotation));
	}

	private function createMappedToNonMasterException(EntityProperty $targetEntityProperty) {
		throw $this->classSetup->createException('Illegal attempt to associate mapped relation property '
						. $this->relationProperty->toPropertyString() . ' with non-master property '
						. $targetEntityProperty->toPropertyString() . '.',
				null, array($this->relationAnnotation));
	}

	public function createMasterToOneRelation(EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$namingStrategy = $this->classSetup->getNamingStrategy();

		if (null !== $this->annoJoinTable) {
			$entityModel = $this->relationProperty->getEntityModel();
			$class = $entityModel->getClass();
				
			$relation = new JoinTableToOneRelation($this->relationProperty, $targetEntityModel);
			$relation->setJoinTableName($namingStrategy->buildJunctionTableName($entityModel->getTableName(),
					$this->relationProperty->getName(), $this->annoJoinTable->getName()));
			$relation->setJoinColumnName($namingStrategy->buildJunctionJoinColumnName($class, $entityModel->getIdDef()->getPropertyName(),
					$this->annoJoinTable->getJoinColumnName()));
			$relation->setInverseJoinColumnName($namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
					$targetEntityModel->getIdDef()->getPropertyName(),
					$this->annoJoinTable->getInverseJoinColumnName()));
			$this->completeRelation($relation);
			return $relation;
		}

		$joinColumnName = null;
		if (null !== $this->annoJoinColumn) {
			$joinColumnName = $this->annoJoinColumn->getName();
		}
		
		$joinColumnName = $namingStrategy->buildJoinColumnName($this->relationProperty->getName(), 
				$targetEntityModel->getIdDef()->getPropertyName(), $joinColumnName);

		$relation = new JoinColumnToOneRelation($this->relationProperty, $targetEntityModel);
		$relation->setJoinColumnName($this->classSetup->requestColumn($this->relationProperty->getName(),
				$joinColumnName, array($this->annoJoinColumn)));
		
		$this->completeRelation($relation);
		return $relation;
	}

	const JOIN_MODE_COLUMN = 'joinColumn';
	const JOIN_MODE_TABLE = 'joinTable';
	
	public static function detectJoinMode($type, bool $annoJoinColumnAvailable,
			bool $annoJoinTableAvailable): string {
		
		switch ($type) {
			case RelationEntityProperty::TYPE_MANY_TO_ONE:
			case RelationEntityProperty::TYPE_ONE_TO_ONE:
				if ($annoJoinTableAvailable) return self::JOIN_MODE_TABLE;
				return self::JOIN_MODE_COLUMN;
			case RelationEntityProperty::TYPE_ONE_TO_MANY:
			case RelationEntityProperty::TYPE_MANY_TO_MANY:
				if ($annoJoinColumnAvailable) return self::JOIN_MODE_COLUMN;
				return self::JOIN_MODE_TABLE;
			default:
				throw new \InvalidArgumentException();
		}
	}
	
	public function createMasterToManyRelation(EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);

		$namingStrategy = $this->classSetup->getNamingStrategy();
		
		$orderDirectives = $this->determineOrderDirectives($targetEntityModel);

		if (null !== $this->annoJoinColumn) {
			if ($this->relationProperty->getType() != RelationEntityProperty::TYPE_ONE_TO_MANY) {
				throw $this->classSetup->createException('Invalid annotation for ' . $this->relationProperty->getType()
						. ' property', null, array($this->annoJoinColumn));
			}

			$joinColumnName = $this->annoJoinColumn->getName();
			if ($joinColumnName === null) {
				$namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
						$targetEntityModel->getIdDef()->getPropertyName(), $joinColumnName);
			}
				
			$relation = new InverseJoinColumnOneToManyRelation($this->relationProperty, $targetEntityModel);
			$relation->setInverseJoinColumnName($joinColumnName);
			$relation->setOrderDirectives($orderDirectives);
			$this->completeRelation($relation);
			return $relation;
		}

		$joinTableName = null;
		$joinColumnName = null;
		$inverseJoinColumnName = null;
		if (null !== $this->annoJoinTable) {
			$joinTableName = $this->annoJoinTable->getName();
			$joinColumnName = $this->annoJoinTable->getJoinColumnName();
			$inverseJoinColumnName = $this->annoJoinTable->getInverseJoinColumnName();
		}

		$entityModel = $this->relationProperty->getEntityModel();
		$class = $entityModel->getClass();

		$relation = new JoinTableToManyRelation($this->relationProperty, $targetEntityModel);
		$relation->setJoinTableName($namingStrategy->buildJunctionTableName($entityModel->getTableName(),
				$this->relationProperty->getName(), $joinTableName));
		$relation->setJoinColumnName($namingStrategy->buildJunctionJoinColumnName($class, $entityModel->getIdDef()->getPropertyName(),
				$joinColumnName));
		$relation->setInverseJoinColumnName($namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
				$targetEntityModel->getIdDef()->getPropertyName(), $inverseJoinColumnName));
		$relation->setOrderDirectives($orderDirectives);
		$this->completeRelation($relation);
		return $relation;
	}

	private function determineTargetEntityModel(EntityModelManager $entityModelManager) {
		$targetEntityModel = null;
		try {
			$targetEntityModel = $entityModelManager->getEntityModelByClass($this->relationAnnotation->getTargetEntityClass());
		} catch (OrmConfigurationException $e) {
			throw $this->classSetup->createException($this->classSetup->buildPropertyString(
					$this->relationProperty->getName())
					. ' is annotated with invalid target entity class.', $e,
					array($this->relationAnnotation));
		}
		
		$type = $this->relationProperty->getType();
		if (($type == RelationEntityProperty::TYPE_MANY_TO_ONE || $type == RelationEntityProperty::TYPE_ONE_TO_ONE)
				&& $targetEntityModel->hasSubEntityModels()
				&& ($this->relationAnnotation === null || $this->relationAnnotation->getFetchType() !== FetchType::EAGER)) {
			throw $this->classSetup->createException('Lazy fetch disallowed for ' 
							. $this->classSetup->buildPropertyString($this->relationProperty->getName())
							. '. ' . $this->relationProperty->getType() 
							. ' properties which refer to entities which are inherited by other entities must be eager'
							. ' fetched (FetchType::EAGER).', 
					null, array($this->relationAnnotation));
		}
		
		return $targetEntityModel;
	}

	const PROPERTY_NAME_SEPARATOR = '.';

	private function determineTargetEntityProperty($mappedBy, EntityModel $targetEntityModel) {
		$nextPropertyNames = explode(self::PROPERTY_NAME_SEPARATOR, $mappedBy);
		$targetEntityPropertyCollection = $targetEntityModel;

		try {
			$targetEntityProperty = $this->determineEntityProperty($mappedBy, $targetEntityModel);
			if ($targetEntityProperty instanceof RelationEntityProperty) return $targetEntityProperty;
				
			throw $this->classSetup->createException('Illegal attempt to associate relation property '
							. $this->relationProperty->toPropertyString() . ' with non relation property.',
					null, array($this->relationAnnotation));
		} catch (UnknownEntityPropertyException $e) {
			throw $this->classSetup->createException($this->classSetup->getClass()->getName() . '::$'
					. $this->relationProperty->getName() . ' is mapped by unknown entity property '
					. TypeUtils::prettyClassPropName($targetEntityModel->getClass(), $mappedBy) 
					. '.', $e, array($this->relationAnnotation));
		}
	}

	private function determineOrderDirectives(EntityModel $targetEntityModel) {
		if ($this->annoOrderBy === null) return array();
	
		$orderDirectives = array();
		foreach ($this->annoOrderBy->getOrderDefs() as $propertyExpression => $direction) {
			try {
				$propertyNames = array();
				$targetEntityProperty = $this->determineEntityProperty($propertyExpression, 
						$targetEntityModel, $propertyNames);

				if ($targetEntityProperty instanceof QueryItemRepresentableEntityProperty) {
					$orderDirectives[] = new OrderDirective($propertyNames, $direction);
					continue;
				}
				
				throw $this->classSetup->createException('Property '
								. $this->relationProperty->toPropertyString() . ' can not be used in order directives.',
						null, array($this->annoOrderBy));
			} catch (UnknownEntityPropertyException $e) {
				throw $this->classSetup->createException($this->classSetup->getClass()->getName() . '::$'
						. $this->relationProperty->getName() . ' is ordered by unknown entity property \''
						. $propertyExpression . '\'.', $e, array($this->annoOrderBy));
			}
		}
		return $orderDirectives;
	}
	
	private function determineEntityProperty($propertyExpression, EntityModel $entityModel, array &$propertyNames = array()) {
		$nextPropertyNames = explode(self::PROPERTY_NAME_SEPARATOR, $propertyExpression);
		$entityPropertyCollection = $entityModel;
		
		while (null !== ($propertyName = array_shift($nextPropertyNames))) {
			$propertyNames[] = $propertyName;
			$entityProperty = $entityPropertyCollection->getEntityPropertyByName($propertyName);
			if (empty($nextPropertyNames)) {
				return $entityProperty;					
			}
	
			if ($entityProperty->hasEmbeddedEntityPropertyCollection()) {
				$entityPropertyCollection = $entityProperty->getEmbeddedEntityPropertyCollection();
				continue;
			}
	
			throw new UnknownEntityPropertyException('Unresolvable entity property: '
					. $this->relationProperty->getEntityModel()->getClass()->getName() . '::$'
					. implode('::$', $propertyNames));
		}
	}
}
