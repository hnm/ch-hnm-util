<?php
namespace ch\hnm\util\n2n\bot\filter;

use n2n\web\http\controller\ControllerAdapter;
use ch\hnm\util\n2n\bot\model\BotModel;
use ch\hnm\util\n2n\bot\model\BotUtils;
use n2n\web\http\BadRequestException;
use n2n\io\managed\impl\FileFactory;
use n2n\reflection\annotation\AnnoInit;
use n2n\web\http\annotation\AnnoPath;
use n2n\web\http\annotation\AnnoPost;
use n2n\web\http\annotation\AnnoGet;
use n2n\web\dispatch\DispatchContext;

class BotFilter extends ControllerAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->m('doCheckPost', new AnnoPath('**'), new AnnoPost());
		//assets/ch-hnm-util/img/pixel.png
		$ai->m('doImage', new AnnoPath('*/ch-hnm-util/img/' . BotUtils::HIDDEN_IMAGE_FILE_NAME), new AnnoGet());
	}
	
	public function doCheckPost(BotModel $botHiddenImageModel, DispatchContext $dispatchContext) {
		if (!$botHiddenImageModel->isCheckImage() || $botHiddenImageModel->isImageLoaded()
				|| !$dispatchContext->hasDispatchJob() 
				|| !$botHiddenImageModel->hasDispatchClassName($dispatchContext->getDispatchJob()
						->getDispatchTarget()->getDispatchClassName())) {
			return;
		}
		
		throw new BadRequestException('BotHiddenImageValidation Failed.');
	}
	
	public function doImage(BotModel $botHiddenImageModel) {
		if (!$botHiddenImageModel->isCheckImage()) return;
		
		$botHiddenImageModel->setImageLoaded(true);
		
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'hidden');
		
		$img = imagecreatetruecolor(1, 1);
		
		imagesavealpha($img, true);
		$color = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $color);
		
		imagepng($img, $tmpFilePath);
		$this->getResponse()->send(FileFactory::createFromFs($tmpFilePath, BotUtils::HIDDEN_IMAGE_FILE_NAME));
		
		$this->getControllingPlan()->abort();
	}

}