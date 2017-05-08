<?php
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
	<h3><?php $html->text('rocket_past_imports_title') ?></h3>
	<table class="rocket-list">
		<?php foreach ($importUploads as $importUpload): ?>
			<tr>
				<td><?php $html->out($importUpload->getFile()->getOriginalName()) ?></td>
				<td><?php $html->out(date_format($importUpload->getDateTime(), 'h:m:s d.m.Y')) ?>
                <td><?php $html->text('rocket_import_upload_state_' . $importUpload->determineState()) ?></td>
                <td>
                    <ul class="rocket-simple-controls">
                        <li>
                            <?php $html->linkToController(array('assign', $importUpload->getId()),
								new n2n\web\ui\Raw('<i class="fa fa-pencil"></i> <span>editieren</span>'),
								array('class' => 'rocket-control rocket-important')) ?>
                        </li>

						<?php if ($importUpload->determineState() === \ch\hnm\util\rocket\import\bo\ImportUpload::STATE_FINISHED): ?>
                            <li>
								<?php $html->linkToController(array('removeentries', $importUpload->getId()),
									new n2n\web\ui\Raw('<i class="fa fa-user-times"></i> <span>Einträge in der DB löschen</span>'),
									array('class' => 'rocket-control rocket-important',
										'data-rocket-confirm-msg'=> $html->getText('rocket_import_sure_delete_entities_question'),
										'data-rocket-confirm-ok-label' => $html->getText('rocket_import_yes_label'),
                                        'data-rocket-confirm-cancel-label' => $html->getText('rocket_import_no_label'))) ?>
                            </li>
						<?php endif ?>

                        <li>
							<?php $html->linkToController(array('reset', $importUpload->getId()),
								new n2n\web\ui\Raw('<i class="fa fa-refresh"></i> <span>Reset</span>'),
								array('class' => 'rocket-control rocket-important',
									'data-rocket-confirm-msg' => $html->getText('rocket_import_sure_reset_import_upload_question'),
									'data-rocket-confirm-ok-label' => $html->getText('rocket_import_yes_label') ,
									'data-rocket-confirm-cancel-label'=> $html->getText('rocket_import_no_label'))) ?>
                        </li>

                        <li>
							<?php $html->linkToController(array('delete', $importUpload->getId()),
								new n2n\web\ui\Raw('<i class="fa fa-times"></i> <span>Löschen</span>'),
								array('class' => 'rocket-control rocket-important',
									'data-rocket-confirm-msg' => $html->getText('rocket_import_sure_delete_import_upload_question'),
									'data-rocket-confirm-ok-label' => $html->getText('rocket_import_yes_label') ,
                                    'data-rocket-confirm-cancel-label'=> $html->getText('rocket_import_no_label'))) ?>
                        </li>
                    </ul>
                </td>
			</tr>
		<?php endforeach ?>
	</table>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
			<?php $formHtml->buttonSubmit(null,
				new \n2n\web\ui\Raw('<i class="fa fa-cloud-upload"></i> ' . $html->getL10nText('rocket_import_upload_start_label')),
				array('class' => 'rocket-control-success rocket-important')) ?>
        </li>
    </ul>
</div>

<?php $formHtml->close() ?>
