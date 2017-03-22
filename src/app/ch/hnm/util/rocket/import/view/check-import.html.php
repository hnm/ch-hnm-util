<?php
	use n2n\web\ui\Raw;
	
	$columns = $view->getParam('columns');
	$rows = $view->getParam('rows');
	
	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Import')));
?>
<div class="rocket-panel">
	<h3><?php $html->text('rocket_check_import_file_title') ?></h3>
	<table class="rocket-list">
		<thead>
			<tr>
				<?php foreach($columns as $column): ?>
					<th><?php $html->out($column) ?></th>
				<?php endforeach ?>
			</tr>
		</thead>
		<?php foreach ($rows as $row): ?>
			<tr>
				<?php foreach ($row as $cell): ?>
					<td><?php $html->out($cell) ?></td>
				<?php endforeach ?>
			</tr>
		<?php endforeach ?>
	</table>
</div>
<div id="rocket-page-controls">
	<ul>
		<li>
			<?php $html->linkToUrl(null, new Raw('<i class="fa fa-cloud-upload"></i> ' . $html->getL10nText('common_upload_label')),
						array('class' => 'rocket-control-success rocket-important'), array('c' => 1)) ?>
		</li>
	</ul>
</div>