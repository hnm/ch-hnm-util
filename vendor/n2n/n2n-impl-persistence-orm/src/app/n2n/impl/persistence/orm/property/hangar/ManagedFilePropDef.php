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
use hangar\api\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\util\type\ArgUtils;
use n2n\io\orm\ManagedFileEntityProperty;
use n2n\impl\web\dispatch\mag\model\NumericMag;
use n2n\persistence\orm\annotation\AnnoManagedFile;
use n2n\io\managed\FileManager;
use n2n\impl\web\dispatch\mag\model\ClassNameMag;
use n2n\persistence\meta\structure\common\CommonStringColumn;
use hangar\api\CompatibilityLevel;
use n2n\io\managed\File;
use hangar\api\ColumnDefaults;
use phpbob\representation\PhpTypeDef;
use n2n\util\type\CastUtils;
use hangar\api\HuoContext;
use n2n\persistence\meta\structure\Column;
use n2n\web\dispatch\mag\MagCollection;
use n2n\util\StringUtils;

class ManagedFilePropDef implements HangarPropDef {
	const PROP_NAME_LENGTH = 'length';
	const PROP_NAME_FILE_MANAGER = 'fileManager';
	
	private $columnDefaults;
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getName(): string {
		return 'MangedFile';
	}
	
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$magCollection = new MagCollection();
		
		$size = $this->columnDefaults->getDefaultStringLength();
		$fileManagerLookupId = null;
		if (null !== $propSourceDef) {
			$size = $propSourceDef->getHangarData()->get(self::PROP_NAME_LENGTH, false, $size);
			
			$fileManagerLookupId = FileManager::TYPE_PUBLIC;
			if ($propSourceDef->hasPhpPropertyAnno(AnnoManagedFile::class)) {
				$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoManagedFile::class);
				
				if (null !== ($phpAnnoParam = $phpAnno->getPhpAnnoParam(1))) {
					$fileManagerLookupId = $propSourceDef->determineTypeName(self::determineFileManagerStr($phpAnnoParam));
				} 
			}
		}
		
		$options = [FileManager::TYPE_PRIVATE => FileManager::TYPE_PRIVATE, 
				FileManager::TYPE_PUBLIC => FileManager::TYPE_PUBLIC];
		
		$magCollection->addMag(self::PROP_NAME_LENGTH, new NumericMag('Length', $size, true));
		$magCollection->addMag(self::PROP_NAME_FILE_MANAGER, new ClassNameMag('FileManager (Lookup Id)', 
				new \ReflectionClass(FileManager::class), $fileManagerLookupId, false, null, null, 
				['class' => 'hangar-autocompletion', 'data-suggestions' => StringUtils::jsonEncode($options),
						'data-custom-allowed' => true]));
		
		return $magCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManagedFile::class)) {
			$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoManagedFile::class);
			
			if (null !== ($annoManagedFile = $phpAnno->determineAnnotation())) {
				CastUtils::assertTrue($annoManagedFile instanceof AnnoManagedFile);
				if (null !== ($fileLocator = $annoManagedFile->getFileLocator())) {
					$propSourceDef->removePhpUse(get_class($fileLocator));
				}
			}
			
			$propSourceDef->removePhpUse(AnnoManagedFile::class);
			$propSourceDef->removePhpPropertyAnno(AnnoManagedFile::class);
		}
		
		$propSourceDef->removePhpUse($this->determineFileManagerLookupId($propSourceDef));
	}
	
	private function determineFileManagerLookupId($propSourceDef) {
		$fileManagerLookupId = FileManager::TYPE_PUBLIC;
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManagedFile::class)) {
			$phpAnno = $propSourceDef->getPhpPropertyAnno(AnnoManagedFile::class);
			if (null !== ($phpAnnoParam = $phpAnno->getPhpAnnoParam(1))) {
				$fileManagerLookupId = $propSourceDef->determineTypeName(self::determineFileManagerStr($phpAnnoParam));
			}
		}
		return $fileManagerLookupId;
	}
	
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->setPhpTypeDef(PhpTypeDef::fromTypeName(File::class));
		
		$annoManagedFile = $propSourceDef->getOrCreatePhpPropertyAnno(AnnoManagedFile::class);
		
		$fileManagerLookupId = $dataSet->get(self::PROP_NAME_FILE_MANAGER);
		if ($fileManagerLookupId === FileManager::TYPE_PUBLIC) {
			$fileManagerLookupId = null;
		}
		
		if (null !== $fileManagerLookupId) {
			$phpTypeDef = PhpTypeDef::fromTypeName($fileManagerLookupId);
			$propSourceDef->createPhpUse($phpTypeDef->getTypeName());
			
			$annoManagedFile->getOrCreatePhpAnnoParam(1, $phpTypeDef->getLocalName() . '::class');
		} else if ($annoManagedFile->getNumPhpAnnoParams() > 1) {
			$annoManagedFile->getPhpAnnoParam(1)->setValue('null');
		} else {
			$annoManagedFile->resetPhpAnnoParams();
		}
		
		$propSourceDef->getHangarData()->setAll(array(
				self::PROP_NAME_LENGTH => $dataSet->get(self::PROP_NAME_LENGTH)));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::applyDbMeta()
	 */
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		
		ArgUtils::assertTrue($entityProperty instanceof ManagedFileEntityProperty);
		$columnName = $entityProperty->getColumnName();
		$dbInfo->removeColumn($columnName);
		
		$dbInfo->getTable()->createColumnFactory()
				->createStringColumn($columnName, 
						$propSourceDef->getHangarData()->get(self::PROP_NAME_LENGTH, 
								false, $this->columnDefaults->getDefaultStringLength()));
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		ArgUtils::assertTrue($entityProperty instanceof ManagedFileEntityProperty);
		return new CommonStringColumn($entityProperty->getColumnName(), 
				$propSourceDef->getHangarData()->get(self::PROP_NAME_LENGTH, 
						false, $this->columnDefaults->getDefaultStringLength()));
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::testCompatibility()
	 */
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if ($propSourceDef->hasPhpPropertyAnno(AnnoManagedFile::class)) return CompatibilityLevel::COMMON;
		if (null !== ($phpTypeDef = $propSourceDef->getPhpTypeDef()) &&
				$phpTypeDef->getTypeName() === File::class) return CompatibilityLevel::COMMON;
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
    
    public static function determineFileManagerStr(string $param) {
    	if ($param === 'null') return FileManager::TYPE_PUBLIC;
    	
    	if (StringUtils::endsWith('::class', $param)) {
    		return mb_substr($param, 0, -7);
    	}
    	
    	if (StringUtils::startsWith('\'', $param) && StringUtils::endsWith('\'', $param)) {
    		return mb_substr($param, 1, -1);
    	}
    	
    	throw new \InvalidArgumentException('Invalid file manager param: ' . $param);
    }
    
    /**
     * {@inheritDoc}
     * @see \hangar\api\HangarPropDef::isBasic()
     */
    public function isBasic(): bool {
    	return false;
    }
}
