<?php 
	use ch\hnm\util\rocket\import\form\AssignationForm;

	use n2n\impl\web\ui\view\html\HtmlView;
	
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$formHtml = HtmlView::formHtml($view);
	
	$assignationForm = $view->getParam('assignationForm');
	$view->assert($assignationForm instanceof AssignationForm);

	$importUpload = $view->getParam('importUpload');
	$view->assert($importUpload instanceof \ch\hnm\util\rocket\import\bo\ImportUpload);

	$html->meta()->addJs('import/assignForm.js');
	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('rocket_import_assign_combine_title')));

    $assignationJson = $importUpload->getAssignationJson();
	$assignationJsonArr = null;

	if (null !== $assignationJson) {
		$assignationJsonArr = \n2n\util\StringUtils::jsonDecode($assignationJson, true);
    }
?>
<?php $formHtml->open($assignationForm) ?>

<div class="rocket-panel">
	<h3><?php $html->text('rocket_import_assign_title') ?></h3>
	<?php $view->import('inc\assignTable.html',
            array('scalarEiProperties' => $view->getParam('scalarEiProperties'),
            'csvPropertyNames' => $view->getParam('csvPropertyNames'),
            'assignationJsonArr' => $assignationJsonArr)) ?>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
			<?php $formHtml->buttonSubmit('assign', $html->getText('rocket_import_confirm_label')) ?>
        </li>
    </ul>
</div>

<?php $formHtml->close() ?>
