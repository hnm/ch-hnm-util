<?php
$importViewModel = $view->getParam('ivm');
$view->assert($importViewModel instanceof \ch\hnm\util\rocket\import\model\ImportViewModel);

$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Import')));
?>
<div class="rocket-panel">
	<h3><?php $html->text('rocket_check_import_file_title') ?></h3>
	<div class="rocket-properties">
		<div class="rocket-block rocket-editable rocket-required">

		</div>

		<div id="rocket-page-controls">
			<ul>
				<li>
					<?php $formHtml->open($importViewModel->getImportForm()) ?>
					<?php $formHtml->buttonSubmit('save',
						new \n2n\web\ui\Raw('<i class=""></i> ' . $html->getL10nText('continue_label')),
						array('class' => 'rocket-important')) ?>
					<?php $formHtml->close() ?>
				</li>
			</ul>
		</div>
	</div>
</div>