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
namespace n2n\impl\persistence\orm\property;

use n2n\persistence\orm\property\EntityPropertyProvider;
use n2n\reflection\property\AccessProxy;
use n2n\persistence\orm\property\ClassSetup;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\impl\persistence\orm\property\relation\RelationFactory;
use n2n\persistence\orm\annotation\AnnoManyToMany;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionUtils;
use n2n\persistence\orm\model\NamingStrategy;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\annotation\AnnoEmbedded;
use n2n\io\orm\FileEntityProperty;
use n2n\io\orm\ManagedFileEntityProperty;
use n2n\io\managed\impl\SimpleFileLocator;
use n2n\io\IoUtils;
use n2n\util\uri\Url;
use n2n\persistence\orm\annotation\AnnoBool;
use n2n\util\type\TypeName;

class CommonEntityPropertyProvider implements EntityPropertyProvider {
	const PROP_FILE_NAME_SUFFIX = '.originalName';
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityPropertyProvider::setupPropertyIfSuitable()
	 */
	public function setupPropertyIfSuitable(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {

		$annotationSet = $classSetup->getAnnotationSet();
		$propertyName = $propertyAccessProxy->getPropertyName();
		
		if (null !== ($annoDateTime = $annotationSet->getPropertyAnnotation($propertyName, 
				'n2n\persistence\orm\annotation\AnnoDateTime'))) {
			$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName), array($annoDateTime)));
			return;
		}
		
		if (null !== ($annoN2nLocale = $annotationSet->getPropertyAnnotation($propertyName, 
				'n2n\persistence\orm\annotation\AnnoN2nLocale'))) {
			$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName)), array($annoN2nLocale));
			return;
		}

		if (null !== ($annoUrl = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoUrl'))) {
			$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
					$classSetup->requestColumn($propertyName)), array($annoUrl));
			return;
		}

		if (null !== ($annoLob = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoLob'))) {
				$classSetup->provideEntityProperty(new LobEntityProperty($propertyAccessProxy,
								$classSetup->requestColumn($propertyName)),
						array($annoLob));
				return;
		}
		
		if (null !== ($annoFile = $annotationSet->getPropertyAnnotation($propertyName, 
				'n2n\persistence\orm\annotation\AnnoFile'))) {
			$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy, 
							$classSetup->requestColumn($propertyName), 
							$classSetup->requestColumn($propertyName . self::PROP_FILE_NAME_SUFFIX),
									$annoFile->getOriginalNameColumnName()), 
					array($annoN2nLocale));
			return;
		}
		
		if (null !== ($annoManagedFile = $annotationSet->getPropertyAnnotation($propertyName, 
				'n2n\persistence\orm\annotation\AnnoManagedFile'))) {
			$manageFileEntityProperty = new ManagedFileEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName), $annoManagedFile->getLookupId(),
					$annoManagedFile->isCascadeDelete());
					
			if (null !== ($fileLocator = $annoManagedFile->getFileLocator())) {
				$manageFileEntityProperty->setFileLocator($fileLocator);
			} else {
				$manageFileEntityProperty->setFileLocator(new SimpleFileLocator(
						mb_strtolower(IoUtils::stripSpecialChars($classSetup->getClass()->getShortName()))));
			}
			
			$classSetup->provideEntityProperty($manageFileEntityProperty, array($annoN2nLocale));
			return;
		}
		
		if (null !== ($annoBool = $annotationSet->getPropertyAnnotation($propertyName, AnnoBool::class))) {
			$classSetup->provideEntityProperty(new BoolEntityProperty($propertyAccessProxy,
					$classSetup->requestColumn($propertyName)), array($annoBool));
			return;
		}
		
		switch ($propertyAccessProxy->getConstraint()->getTypeName()) {
			case TypeName::BOOL:
				$classSetup->provideEntityProperty(new BoolEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName)));
				return;
			case TypeName::INT:
				$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName)));
				return;
			case TypeName::FLOAT:
				$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName)));
				return;
			case TypeName::STRING:
				$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName)));
				return;
		}

		if ($this->checkForRelations($propertyAccessProxy, $classSetup)) {
			return;
		}
		
		if ($this->checkForEmbedded($propertyAccessProxy, $classSetup)) {
			return;
		}
		
		$setterMethodName = PropertiesAnalyzer::buildSetterName($propertyName);
		$class = $classSetup->getClass();
		
		if (!$class->hasMethod($setterMethodName)) return;
		
		$setterMethod = $class->getMethod($setterMethodName);
		
		$parameters = $setterMethod->getParameters();
		if (count($parameters) == 0) return;
		$parameter = current($parameters);
		
		if (null !== ($paramClass = ReflectionUtils::extractParameterClass($parameter))) {
			switch ($paramClass->getName()) {
				case 'DateTime':
					$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case 'n2n\l10n\N2nLocale':
					$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case 'n2n\io\managed\File':
					$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName), null));
					break;
				case Url::class:
					$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
			}
		}
		
		if (null !== ($type = $parameter->getType())) {
			switch ($type->getName()) {
				case TypeName::BOOL:
					$classSetup->provideEntityProperty(new BoolEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case TypeName::INT:
					$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
			}
		}
	}
	
	private function checkForEmbedded(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$annotationSet = $classSetup->getAnnotationSet();
		$annoEmbedded = $annotationSet->getPropertyAnnotation($propertyName, 
				'n2n\persistence\orm\annotation\AnnoEmbedded');
		if ($annoEmbedded === null) return false;
		
		ArgUtils::assertTrue($annoEmbedded instanceof AnnoEmbedded);
		
		$embeddedEntityProperty = new EmbeddedEntityProperty($propertyAccessProxy, 
				$annoEmbedded->getTargetClass());	
				
		$classSetup->provideEntityProperty($embeddedEntityProperty);
		
		$setupProcess = $classSetup->getSetupProcess();
		$targetClassSetup = new ClassSetup($setupProcess, $annoEmbedded->getTargetClass(),
				new EmbeddedNampingStrategy($classSetup->getNamingStrategy(), $annoEmbedded->getColumnPrefix(), 
						$annoEmbedded->getColumnSuffix()),
				$classSetup, $propertyName);
		$setupProcess->getEntityPropertyAnalyzer()->analyzeClass($targetClassSetup);

		foreach ($targetClassSetup->getEntityProperties() as $property) {
			$embeddedEntityProperty->addEntityProperty($property);
		}
		
		return true;
	}
	
	private function checkForRelations(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$annotationSet = $classSetup->getAnnotationSet();
		
		if (null !== ($annoOneToOne = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoOneToOne'))) {
			$this->provideOneToOne($propertyAccessProxy, $annoOneToOne, $classSetup);
			return true;
		}
		
		if (null !== ($annoManyToOne = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoManyToOne'))) {
			$this->provideManyToOne($propertyAccessProxy, $annoManyToOne, $classSetup);
			return true;
		}
		
		if (null !== ($annoOneToMany = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoOneToMany'))) {
			$this->provideOneToMany($propertyAccessProxy, $annoOneToMany, $classSetup);
			return true;
		}
		
		if (null !== ($annoManyToMany = $annotationSet->getPropertyAnnotation($propertyName,
				'n2n\persistence\orm\annotation\AnnoManyToMany'))) {
			$this->provideManyToMany($propertyAccessProxy, $annoManyToMany, $classSetup);
			return true;
		}
	}
	
	private function provideOneToOne(AccessProxy $propertyAccessProxy, 
			AnnoOneToOne $annoOneToOne, ClassSetup $classSetup) {
		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy, 
				$annoOneToOne->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);
			
		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $annoOneToOne);
			
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $annoOneToOne, $relationFactory) {
			if (null !== ($mappedBy = $annoOneToOne->getMappedBy())) {
				$toOneProperty->setRelation($relationFactory->createMappedOneToOneRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toOneProperty->setRelation($relationFactory
						->createMasterToOneRelation($entityModelManager));
			}
		}, $toOneProperty->isMaster());
	}
	
	private function provideManyToOne(AccessProxy $propertyAccessProxy, 
			AnnoManyToOne $annoManyToOne, ClassSetup $classSetup) {
		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy, true, 
				RelationEntityProperty::TYPE_MANY_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $annoManyToOne);

		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $relationFactory, $classSetup) {
			$toOneProperty->setRelation($relationFactory->createMasterToOneRelation($entityModelManager));
		}, true);
	}
	
	private function provideOneToMany(AccessProxy $propertyAccessProxy, 
			AnnoOneToMany $annoOneToMany, ClassSetup $classSetup) {
		$toManyProperty = new ToManyEntityProperty($propertyAccessProxy, 
				$annoOneToMany->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_MANY);
		$classSetup->provideEntityProperty($toManyProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toManyProperty, $annoOneToMany);

		if (!$toManyProperty->isMaster()) {
			$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
					use ($toManyProperty, $annoOneToMany, $relationFactory) {
						$entityModelManager->getEntityModelByClass($annoOneToMany->getTargetEntityClass());
			}, true);
		}
			
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toManyProperty, $annoOneToMany, $relationFactory) {
			if (null !== ($mappedBy = $annoOneToMany->getMappedBy())) {
				$toManyProperty->setRelation($relationFactory->createMappedOneToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toManyProperty->setRelation($relationFactory
						->createMasterToManyRelation($entityModelManager));
			}
		}, $toManyProperty->isMaster());
	}
	
	private function provideManyToMany(AccessProxy $propertyAccessProxy,
			AnnoManyToMany $annoManyToMany, ClassSetup $classSetup) {
		$manyToManyProperty = new ToManyEntityProperty($propertyAccessProxy,
				 $annoManyToMany->getMappedBy() === null, RelationEntityProperty::TYPE_MANY_TO_MANY);
		$classSetup->provideEntityProperty($manyToManyProperty);
			
		$relationFactory = new RelationFactory($classSetup, $manyToManyProperty, $annoManyToMany);
		
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($manyToManyProperty, $annoManyToMany, $relationFactory) {
			if (null !== ($mappedBy = $annoManyToMany->getMappedBy())) {
				$manyToManyProperty->setRelation($relationFactory->createMappedManyToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$manyToManyProperty->setRelation($relationFactory->createMasterToManyRelation(
						$entityModelManager));
			}
		}, $manyToManyProperty->isMaster());
	}
}


class EmbeddedNampingStrategy implements NamingStrategy {
	private $decoratedNamingStrategie;
	private $prefix;
	private $suffix;
	
	public function __construct(NamingStrategy $decoratedNamingStrategy, $prefix = null, $suffix = null) {
		$this->decoratedNamingStrategie = $decoratedNamingStrategy;
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}
	
	public function buildTableName(\ReflectionClass $class, string $tableName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildTableName($class, $tableName) 
				. $this->suffix;
	}

	public function buildJunctionTableName(string $ownerTableName, string $propertyName, string $tableName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJunctionTableName($ownerTableName, 
				$propertyName, $tableName) . $this->suffix;
	}

	public function buildColumnName(string $propertyName, string $columnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildColumnName($propertyName, 
				$columnName) . $this->suffix;
	}

	public function buildJunctionJoinColumnName(\ReflectionClass $targetClass, string $targetIdPropertyName,
			string $joinColumnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJunctionJoinColumnName($targetClass, 
				$targetIdPropertyName, $joinColumnName) . $this->suffix;
	}
	
	public function buildJoinColumnName(string $propertyName, string $targetIdPropertyName, string $joinColumnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJoinColumnName($propertyName, 
				$targetIdPropertyName, $joinColumnName) . $this->suffix;
	}
}
