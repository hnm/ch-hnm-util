<?php
	use n2n\web\ui\Raw;

	$importForm = $view->getParam('importForm');
    $view->assert($importForm instanceof \ch\hnm\util\rocket\import\form\ImportForm);

    $importUploads = $view->getParam('importUploads');

	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Import')));
?>

<?php $formHtml->open($importForm) ?>
<div class="rocket-panel">
	<h3><?php $html->text('rocket_import_title') ?></h3>
    <?php $formHtml->messageList() ?>

	<div class="rocket-properties">
		<div class="rocket-block rocket-editable rocket-required">
			<?php $formHtml->label('file') ?>
			<div class="rocket-controls">
				<?php $formHtml->inputFile('file') ?>
			</div>
		</div>
	</div>
</div>

<div class="rocket-panel">
	<h3><?php $html->text('rocket_check_import_file_title') ?></h3>
	<table class="rocket-list">
		<?php foreach ($importUploads as $upload): ?>
			<tr>
				<td><?php $html->out($upload->getFile()->getOriginalName()) ?></td>
				<td><?php $html->out(date_format($upload->getDateTime(), 'h:m:s d.m.Y')) ?>

				<td><?php $html->linkToController(array('edit', $upload->getId()), new Raw('<i class="fa fa-pencil"></i> <span>editieren</span>'),
						array('class' => 'rocket-control rocket-important')) ?></td>

                <td><?php $html->linkToController(array('delete', $upload->getId()), new Raw('<i class="fa fa-times"></i> <span>Löschen</span>'),
						array('class' => 'rocket-control rocket-important', 'data-rocket-confirm-msg'=>'Sind Sie sicher, dass Sie Recipient Category #1 löschen möchten?', 
								'data-rocket-confirm-ok-label' => 'Ja', 'data-rocket-confirm-cancel-label'=>'Nein')) ?></td>
			</tr>
		<?php endforeach ?>
	</table>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
			<?php $formHtml->buttonSubmit(null,
				new \n2n\web\ui\Raw('<i class="fa fa-cloud-upload"></i> ' . $html->getL10nText('common_upload_label')),
				array('class' => 'rocket-control-success rocket-important')) ?>
        </li>
    </ul>
</div>

<?php $formHtml->close() ?>
