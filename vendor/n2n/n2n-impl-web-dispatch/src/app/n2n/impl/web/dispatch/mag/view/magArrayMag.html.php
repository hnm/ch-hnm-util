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
	
	use n2n\web\dispatch\map\PropertyPath;
	use \n2n\web\dispatch\mag\UiOutfitter;
	use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\mag\model\MagArrayMag;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\ui\view\html\HtmlElement;
	/**
	 * @var \n2n\web\ui\view\View $view
	 */
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$formHtml = HtmlView::formHtml($view);
	
	$propertyPath = $view->getParam('propertyPath');
	$view->assert($propertyPath instanceof PropertyPath);
	
	/**
	 * @var UiOutfitter $uiOutfitter
	 */
	$uiOutfitter = $view->getParam('uiOutfitter');
	$view->assert($uiOutfitter instanceof \n2n\web\dispatch\mag\UiOutfitter);
	$numExisting = $view->getParam('numExisting');
	$num = $view->getParam('num');
	
	$html->meta()->addJs('js/mag-collection.js', 'n2n\impl\web\dispatch', false, false, null,
			\n2n\impl\web\ui\view\html\HtmlBuilderMeta::TARGET_BODY_END);
	
	$attrsNature = UiOutfitter::NATURE_MASSIVE_ARRAY_ITEM + UiOutfitter::NATURE_MASSIVE_ARRAY_ITEM_CONTROL;
?>
<div class="n2n-impl-web-dispatch-mag-collection" data-mag-collection-item-existing="<?php $html->out($numExisting) ?>"
	data-mag-collection-item-adder-class="<?php $html->out(\n2n\web\dispatch\mag\MagCollection::CONTROL_ADD_CLASS) ?>"
	data-mag-collection-item-remover-class="<?php $html->out(\n2n\web\dispatch\mag\MagCollection::CONTROL_REMOVE_CLASS) ?>"
	data-mag-collection-show-count="<?php $html->out($numExisting) ?>">

	<div class="n2n-impl-web-dispatch-mag-collection-items">
		<?php $formHtml->meta()->arrayProps($propertyPath, function() 
				use ($formHtml, $view, $uiOutfitter, $html, $attrsNature, $propertyPath) { ?>
			<div class="n2n-impl-web-dispatch-mag-collection-item">
				<?php $formHtml->optionalObjectEnabledHidden() ?>
				
				<?php $formHtml->magOpen('div', MagArrayMag::PROPERTY_NAME)?>
					<div <?php $view->out(HtmlElement::buildAttrsHtml($uiOutfitter->createAttrs(\n2n\web\dispatch\mag\UiOutfitter::NATURE_CONTROL_WRAPPER))) ?>>
						<div <?php $view->out(HtmlElement::buildAttrsHtml($uiOutfitter->createAttrs(\n2n\web\dispatch\mag\UiOutfitter::NATURE_CONTROL_GROUP))) ?>>
							<?php $formHtml->magField() ?>
							<?php $formHtml->message() ?>
							<span <?php $view->out(HtmlElement::buildAttrsHtml($uiOutfitter->createAttrs(\n2n\web\dispatch\mag\UiOutfitter::NATURE_CONTROL_GROUP_ADDON))) ?>>
								<?php $html->out($uiOutfitter->createElement(UiOutfitter::EL_NATURE_CONTROL_REMOVE, 
										array('class' => MagCollection::CONTROL_REMOVE_CLASS), '')) ?>
							</span>
						</div>
					</div>
				<?php $formHtml->magClose() ?>
			</div>
		<?php }, $num) ?>
	</div>
	
	<?php $html->out($uiOutfitter->createElement(UiOutfitter::EL_NATURE_CONTROL_ADD,
			array('class' => \n2n\web\dispatch\mag\MagCollection::CONTROL_ADD_CLASS),
            $html->getText('n2n_impl_mag_collection_add', 
            array('itemName' => $formHtml->meta()->getLabel($propertyPath))))) ?>
</div>