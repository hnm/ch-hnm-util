<?php 
	use rocket\spec\ei\manage\generic\CommonScalarEiProperty;
use ch\hnm\util\rocket\import\form\AssignationForm;

	$scalarEiProperties = $view->getParam('scalarEiProperties');
	$csvPropertyNames = $view->getParam('csvPropertyNames');
	
	$assignationForm = $view->getParam('assignationForm');
	$view->assert($assignationForm instanceof AssignationForm);

	$html->meta()->addJs('import/assignForm.js');
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
            <?php foreach ($scalarEiProperties as $scalarEiProperty): ?>
                <?php $scalarEiPropertyFieldPath = $scalarEiProperty->getEiFieldPath() ?>
                <tr>
                    <th>
                        <?php $html->out((string) $scalarEiProperty->getLabelLStr()) ?>
                    </th>
                    <?php foreach ($csvPropertyNames as $csvPropName): ?>
                        <td>
							<?php if (strpos(strtolower($csvPropName), strtolower((string) $scalarEiProperty->getLabelLStr())) > -1): ?>
                                <?php $formHtml->inputRadio('assignationMap[' . $csvPropName. ']',
                                    (string) $scalarEiPropertyFieldPath, array('checked' => 1)) ?>
                            <?php else: ?>
								<?php $formHtml->inputRadio('assignationMap[' . $csvPropName. ']',
									(string) $scalarEiPropertyFieldPath) ?>
							<?php endif ?>
                        </td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
		</tbody>
	</table>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
			<?php $formHtml->buttonSubmit('assign', 'abschicken') ?>
        </li>
    </ul>
</div>

<?php $formHtml->close() ?>
