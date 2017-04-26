<?php
	use n2n\web\ui\Raw;

	$csv = $view->getParam('csv');
	$view->assert($csv instanceof \ch\hnm\util\rocket\import\bo\Csv);

	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Import')));
?>
<div class="rocket-panel">
	<h3><?php $html->text('rocket_check_import_file_title') ?></h3>
	<table class="rocket-list">
		<thead>
			<tr>
				<?php foreach($csv->getColumnNames() as $columnName): ?>
					<th><?php $html->out($columnName) ?></th>
				<?php endforeach ?>
			</tr>
        </thead>
		<?php foreach ($csv->getCsvLines() as $cl): ?>
            <tr>
				<?php foreach ($cl->getValues() as $value): ?>
                    <td><?php $html->out($value) ?></td>
				<?php endforeach ?>
            </tr>
		<?php endforeach ?>
	</table>
</div>
<div id="rocket-page-controls">
	<ul>
		<li>
			<?php $html->linkToUrl(null, new Raw('<i class="fa fa-cloud-upload"></i> ' . $html->getL10nText('rocket_import_confirm_label')),
						array('class' => 'rocket-control-success rocket-important'), array('c' => 1)) ?>
		</li>
	</ul>
</div>