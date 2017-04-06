<?php 
	use rocket\spec\ei\manage\generic\CommonScalarEiProperty;
use ch\hnm\util\rocket\import\form\AssignationForm;

	$eiProperties = $view->getParam('scalarEiProperties');
	$csvPropertyNames = $view->getParam('csvPropertyNames');
	
	$assignationForm = $view->getParam('assignationForm');
	$view->assert($assignationForm instanceof AssignationForm);
	
	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Assert')));
?>
<?php $formHtml->open($assignationForm) ?>
<div class="rocket-panel">
	<h3>Assign</h3>
	
	<table class="rocket-list">
		<thead>
			<tr>
				<th></th>
				<?php foreach ($eiProperties as $eiProperty): $eiProperty instanceof CommonScalarEiProperty ?>
					<th><?php $html->out($eiProperty->getLabelLstr()) ?></th>
				<?php endforeach ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($csvPropertyNames as $csvPropName): ?>
				<tr>
					<th><?php $html->out($csvPropName) ?></th>
					<?php foreach ($eiProperties as $eiProperty): ?>
						<td>
							<?php $formHtml->inputRadio('assignations[' . $eiProperty->getLabelLstr() . ']', $csvPropName) ?>
						</td>
					<?php endforeach ?>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $formHtml->buttonSubmit('assign', 'abschicken') ?>
<?php $formHtml->close() ?>