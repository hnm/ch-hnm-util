<?php
	$importForm = $view->getParam('importForm');
    $view->assert($importForm instanceof \ch\hnm\util\rocket\import\form\ImportForm);

    $importUploads = $view->getParam('importUploads');

	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Import')));
?>
<div class="rocket-panel">
	<h3><?php $html->text('rocket_import_title') ?></h3>
	<?php $formHtml->open($importForm) ?>

	    <?php $formHtml->messageList() ?>

		<div class="rocket-properties">
			<div class="rocket-block rocket-editable rocket-required">
				<?php $formHtml->label('file') ?>
				<div class="rocket-controls">
					<?php $formHtml->inputFile('file') ?>
				</div>
			</div>

			<div id="rocket-page-controls">
				<ul>
					<li>
						<?php $formHtml->buttonSubmit('save',
							new \n2n\web\ui\Raw('<i class="fa fa-cloud-upload"></i> ' . $html->getL10nText('common_upload_label')),
							array('class' => 'rocket-control-success rocket-important')) ?>
					</li>
				</ul>
			</div>
		</div>

	<?php $formHtml->close() ?>
</div>