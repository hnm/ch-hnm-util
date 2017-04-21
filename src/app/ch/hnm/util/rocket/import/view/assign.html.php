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
                <?php foreach ($csvPropertyNames as $csvPropName): ?>
                    <th><?php $html->out($csvPropName) ?></th>
                <?php endforeach ?>
			</tr>
		</thead>
		<tbody>
            <?php foreach ($eiProperties as $scalarEiProperty): ?>
                <tr>
                    <th>
                        <?php $html->out((string) $scalarEiProperty->getLabelLStr()) ?>
                    </th>
                    <?php foreach ($csvPropertyNames as $csvPropName): ?>
                        <td>
                            <?php $formHtml->inputRadio('assignationMap[' . $csvPropName  . ']',
                                (string) $scalarEiProperty->getEiFieldPath()) ?>
                        </td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $formHtml->buttonSubmit('assign', 'abschicken') ?>
<?php $formHtml->close() ?>