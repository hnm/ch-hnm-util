<?php
namespace ch\hnm\util\n2n\bot\model;

use n2n\core\container\N2nContext;

class BotUtils {
	const HIDDEN_IMAGE_FILE_NAME = 'pixel.png';
	
	public static function buildHiddenImageUrl(N2nContext $n2nContext) {
		return $n2nContext->lookup(\n2n\web\http\HttpContext::class)->getAssetsUrl('ch\hnm\util', true)
				->extR(array('img', self::HIDDEN_IMAGE_FILE_NAME));
	}
}