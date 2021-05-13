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

use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use n2n\impl\web\dispatch\mag\model\MultiSelectMag;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\FetchType;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\util\StringUtils;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\orm\annotation\AnnoManyToMany;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\io\IoUtils;
use phpbob\analyze\PhpSourceAnalyzer;
use n2n\util\type\CastUtils;
use n2n\core\TypeLoader;
use phpbob\PhpbobUtils;
use phpbob\representation\PhpClass;
use n2n\persistence\orm\annotation\AnnoManyToOne;

class OrmRelationMagCollection extends MagCollection {
	const PROP_NAME_TARGET_ENTITY_CLASS = 'targetEntityClass';
	const PROP_NAME_MAPPED_BY = 'mappedBy';
	const PROP_NAME_CASCADE_TYPE = 'cascadeType';
	const PROP_NAME_FETCH_TYPE = 'fetchType';
	const PROP_NAME_ORPHAN_REMOVAL = 'orphanRemoval';
	
	private $targetEntityClassOptions = [];
	private $groupedMappedByOptions = [];
	private $mappedByOptions = [];
	
	public function __construct(EntityModelManager $emm, bool $addMappedBy = true, bool $addOrphanRemoval = false) {
		$this->targetEntityClassOptions = [];
		foreach ($emm->getRegisteredClassNames() as $entityClassName) {
			$this->targetEntityClassOptions[$entityClassName] = $entityClassName;
		}
		
		$this->addMag(self::PROP_NAME_TARGET_ENTITY_CLASS, new EnumMag('Target Entity',
				[null => null] + $this->targetEntityClassOptions, null, true,
				array('class' => $addMappedBy ? 'hangar-orm-relation-target-entity'  : 'hangar-autocompletion'), 
				array('class' => 'hangar-orm-relation-target-entity-container')));
		
		if ($addMappedBy) {
			$analyzer = new PhpSourceAnalyzer();
			foreach ($this->targetEntityClassOptions as $entityClassName) {
				$phpFile = $analyzer->analyze(IoUtils::getContents(TypeLoader::getFilePathOfType($entityClassName)));
				$phpClass = $phpFile->getPhpNamespace(PhpbobUtils::extractNamespace($entityClassName))
						->getPhpType(PhpbobUtils::extractClassName($entityClassName));
				CastUtils::assertTrue($phpClass instanceof PhpClass);
				foreach (array_keys($phpClass->getPhpProperties()) as $propertyName) {
					$phpAnnoCollection = $phpClass->getPhpAnnotationSet()->getOrCreatePhpPropertyAnnoCollection($propertyName);
					$phpAnno = null;
					if ($phpAnnoCollection->hasPhpAnno(AnnoOneToMany::class)) {
						$phpAnno = $phpAnnoCollection->getPhpAnno(AnnoOneToMany::class);
					} elseif ($phpAnnoCollection->hasPhpAnno(AnnoManyToMany::class)) {
						$phpAnno = $phpAnnoCollection->getPhpAnno(AnnoManyToMany::class);
					} elseif ($phpAnnoCollection->hasPhpAnno(AnnoOneToOne::class)) {
						$phpAnno = $phpAnnoCollection->getPhpAnno(AnnoOneToOne::class);
					}elseif ($phpAnnoCollection->hasPhpAnno(AnnoManyToOne::class)) {
						$phpAnno = $phpAnnoCollection->getPhpAnno(AnnoManyToOne::class);
					}
					
					if (null === $phpAnno) continue;
					
					$this->groupedMappedByOptions[$entityClassName][$propertyName] = $phpClass->determineTypeName(
							preg_replace('/::getClass\(\)$/', '', (string) $phpAnno->getPhpAnnoParam(1)));
					$this->mappedByOptions[$propertyName] = $propertyName;
				}
			}
			
			$this->addMag(self::PROP_NAME_MAPPED_BY, 
					new StringMag('Mapped By', null, false, null, false,
							array('class' => 'hangar-orm-relation-mapped-by-container'),
							array('class' => 'hangar-orm-relation-mapped-by', 
									'data-grouped-options' => StringUtils::jsonEncode($this->groupedMappedByOptions),
									'data-mapped-by-options' => StringUtils::jsonEncode($this->mappedByOptions))));
		}
		
		$this->addMag(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE, 
				new MultiSelectMag('Cascade Type', self::getCascadeTypeOptions(), array(), 0, null, 
						array('class' => 'hangar-orm-relation-cascade-type'), 
						array('class' => 'hangar-orm-relation-cascade-type-container')));

		$this->addMag(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE, 
				new EnumMag('Fetch Type', self::getFetchTypeOptions(), FetchType::LAZY, true));
		
		if ($addOrphanRemoval) {
			$this->addMag(OrmRelationMagCollection::PROP_NAME_ORPHAN_REMOVAL, 
					new BoolMag('Orphan removal', false));
		}
	}
	
	public function getTargetEntityClassOptions() {
		return $this->targetEntityClassOptions;
	}
	
	public function getGroupedMappedByOptions() {
		return $this->groupedMappedByOptions;
	}

	public function getMappedByOptions() {
		return $this->mappedByOptions;
	}
	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		parent::setupBindingDefinition($bd);
		
// 		if ($this->containsPropertyName(self::PROP_NAME_MAPPED_BY)) {
// 			$that = $this;
// 			$bd->closure(function($targetEntityClass, $mappedBy, BindingErrors $be) use ($that) {
// 				if (!$mappedBy) return;
// 				if (isset($that->groupedMappedByOptions[$targetEntityClass][$mappedBy])) return;
// 				//@todo check TargetEntityClassName
				
// 				$be->addError(self::PROP_NAME_MAPPED_BY, 'Invalid mapped by property name');
// 			});
// 		}
	}
	

	public function setTargetEntityClasName(string $targetEntityClassName) {
		$this->getMagByPropertyName(self::PROP_NAME_TARGET_ENTITY_CLASS)->setValue($targetEntityClassName);
	}
	
	public function setMappedBy(string $mappedBy = null) {
		$this->getMagByPropertyName(self::PROP_NAME_MAPPED_BY)->setValue($mappedBy);
	}
	
	public function setCascadeTypes(array $cascadeTypes) {
		$this->getMagByPropertyName(self::PROP_NAME_CASCADE_TYPE)->setValue($cascadeTypes);
	}
	
	public function setFetchType($fetchType) {
		$this->getMagByPropertyName(self::PROP_NAME_FETCH_TYPE)->setValue($fetchType);
	}
	
	public function setOrphanRemoval(bool $orphanRemoval) {
		$this->getMagByPropertyName(self::PROP_NAME_ORPHAN_REMOVAL)->setValue($orphanRemoval);
	}
	
	public static function buildFetchTypeAnnoParam($fetchType, $addTypeName = true) {
		switch ($fetchType) {
			case FetchType::LAZY:
				return null;
			case FetchType::EAGER:
				return '\n2n\persistence\orm\FetchType::EAGER';
			default:
				throw new IllegalStateException('Invalid fetch Type: ' . $fetchType);
		}
	}
	
	public static function getCascadeTypeOptions() {
		return array(CascadeType::PERSIST => 'PERSIST',
				CascadeType::MERGE => 'MERGE', CascadeType::REMOVE => 'REMOVE', CascadeType::REFRESH => 'REFRESH',
				CascadeType::DETACH => 'DETACH');
	}
	
	public static function getFetchTypeOptions() {
		return array(FetchType::EAGER => 'Eager', FetchType::LAZY => 'Lazy');
	}
	
	public static function buildCascadeTypeAnnoParam(array $cascadeTypes, $addTypeName = true) {
		$nameParts = array();
		if (in_array(CascadeType::PERSIST, $cascadeTypes)
				&& in_array(CascadeType::MERGE, $cascadeTypes)
				&& in_array(CascadeType::REFRESH, $cascadeTypes)
				&& in_array(CascadeType::REMOVE, $cascadeTypes)
				&& in_array(CascadeType::DETACH, $cascadeTypes)) {
			return '\\n2n\\persistence\\orm\\CascadeType::ALL';
		}
		
		foreach ($cascadeTypes as $cascadeType) {
			switch ((int) $cascadeType) {
				case CascadeType::PERSIST:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::PERSIST';
					break;
				case CascadeType::MERGE:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::MERGE';
					break;
				case CascadeType::REFRESH:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::REFRESH';
					break;
				case CascadeType::REMOVE:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::REMOVE';
					break;
				case CascadeType::DETACH:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::DETACH';
					break;
				default:
					throw new IllegalStateException('Invalid cascade Type: ' . $cascadeType);
			}
		}
		
		if (empty($nameParts)) {
			return null;
		}
		
		return implode('|', $nameParts);
	}

	private static function buildCascadeTypes($cascadeType) {
		$cascadeTypes = array();
		if ($cascadeType & CascadeType::DETACH) {
			$cascadeTypes[CascadeType::DETACH] = CascadeType::DETACH;
		}
		
		if ($cascadeType & CascadeType::MERGE) {
			$cascadeTypes[CascadeType::MERGE] = CascadeType::MERGE;
		}
		
		if ($cascadeType & CascadeType::PERSIST) {
			$cascadeTypes[CascadeType::PERSIST] = CascadeType::PERSIST;
		}
		
		if ($cascadeType & CascadeType::REFRESH) {
			$cascadeTypes[CascadeType::REFRESH] = CascadeType::REFRESH;
		}
		
		if ($cascadeType & CascadeType::REMOVE) {
			$cascadeTypes[CascadeType::REMOVE] = CascadeType::REMOVE;
		}
		
		return $cascadeTypes;
	}
	
	public static function determineLocalName(string $param) {
		if (StringUtils::endsWith('::getClass()', $param)) {
			return mb_substr($param, 0, -12);
		}
		
		$reflClassParam = null;
		if (StringUtils::startsWith('new \ReflectionClass(', $param)) {
			$reflClassParam = mb_substr($param, 21, -1);
		} else if (StringUtils::startsWith('ReflectionUtils::createReflectionClass(', $param)) {
			$reflClassParam = mb_substr($param, 39, -1);
		}
		
		if (null !== $reflClassParam) {
			if (StringUtils::startsWith('\'', $reflClassParam)) {
				return mb_substr($reflClassParam, 1, -1);
			}
			
			if (StringUtils::endsWith('::class', $reflClassParam)) {
				return mb_substr($reflClassParam, 0, -7);
			}
		}
		
		throw new \InvalidArgumentException('Invalid Reflection Class Param given.');
	}
	
	public static function determineCascadeTypes(string $param) {
		if ('null' === $param) return [];
		
		$cascadeTypes = [];
		foreach (explode('|', $param) as $paramPart) {
			switch ($paramPart) {
				case 'CascadeType::DETACH':
				case '\n2n\persistence\orm\CascadeType::DETACH':
					$cascadeTypes[CascadeType::DETACH] = CascadeType::DETACH;
					break;
				case 'CascadeType::MERGE':
				case '\n2n\persistence\orm\CascadeType::MERGE':
					$cascadeTypes[CascadeType::MERGE] = CascadeType::MERGE;
					break;
				case 'CascadeType::PERSIST':
				case '\n2n\persistence\orm\CascadeType::PERSIST':
					$cascadeTypes[CascadeType::PERSIST] = CascadeType::PERSIST;
					break;
				case 'CascadeType::REFRESH':
				case '\n2n\persistence\orm\CascadeType::REFRESH':
					$cascadeTypes[CascadeType::REFRESH] = CascadeType::REFRESH;
					break;
				case 'CascadeType::REMOVE':
				case '\n2n\persistence\orm\CascadeType::REMOVE':
					$cascadeTypes[CascadeType::REMOVE] = CascadeType::REMOVE;
					break;
				case 'CascadeType::ALL':
				case '\n2n\persistence\orm\CascadeType::ALL':
					$cascadeTypes[CascadeType::DETACH] = CascadeType::DETACH;
					$cascadeTypes[CascadeType::MERGE] = CascadeType::MERGE;
					$cascadeTypes[CascadeType::PERSIST] = CascadeType::PERSIST;
					$cascadeTypes[CascadeType::REFRESH] = CascadeType::REFRESH;
					$cascadeTypes[CascadeType::REMOVE] = CascadeType::REMOVE;
					break;
				default: 
					throw new \InvalidArgumentException('Invalid cascade type param given: ' . $paramPart);
			}
		}
		return $cascadeTypes;
	}
	
	public static function determineFetchType(string $param) {
		if ('null' === $param) return FetchType::LAZY;
		
		switch ($param) {
			case 'FetchType::EAGER':
			case '\n2n\persistence\orm\FetchType::EAGER':
				return FetchType::EAGER;
			case 'FetchType::LAZY':
			case '\n2n\persistence\orm\FetchType::LAZY':
				return FetchType::LAZY;
		}
		
		throw new \InvalidArgumentException('Invalid fetch type param given: ' . $param);
	}
}