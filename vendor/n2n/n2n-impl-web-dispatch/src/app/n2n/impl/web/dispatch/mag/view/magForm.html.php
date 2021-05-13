<?php
/**
 * @var \n2n\web\ui\view\View $view
 */
$view = \n2n\impl\web\ui\view\html\HtmlView::view($view);
$formHtml = \n2n\impl\web\ui\view\html\HtmlView::formHtml($view);

$propertyPath = $view->getParam('propertyPath', false);
$view->assert($propertyPath === null || $propertyPath instanceof \n2n\web\dispatch\map\PropertyPath);
?>
<?php $view->out('<ul class="n2n-option-collection">') ?>
	<?php $formHtml->meta()->objectProps($propertyPath, function () use ($formHtml) { ?>
		<?php $formHtml->magOpen('li', null, null, new \n2n\impl\web\dispatch\mag\model\BasicUiOutfitter()) ?>
			<?php $formHtml->magLabel() ?>
			<div>
				<?php $formHtml->magField() ?>
			</div>
		<?php $formHtml->magClose() ?>
	<?php }) ?>
<?php $view->out('</ul>') ?>