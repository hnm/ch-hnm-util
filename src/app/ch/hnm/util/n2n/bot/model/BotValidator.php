<?php 
namespace ch\hnm\util\n2n\bot\model;

use n2n\web\dispatch\map\val\Validator;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\MappingResult;
use n2n\util\type\CastUtils;
use n2n\web\http\BadRequestException;

class BotValidator implements Validator {
	public function validate(MappingResult $mappingResult, N2nContext $n2nContext) {
		$botModel = $n2nContext->lookup(BotModel::class);
		CastUtils::assertTrue($botModel instanceof BotModel);

		if ($botModel->isImageLoaded()) return;

		throw new BadRequestException();
	}
} 