<?php
	use n2n\impl\web\ui\view\html\HtmlView;
	
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	
	$assignationMap = $view->getParam('assignationMap');
	$scalarEiProperties = $view->getParam('scalarEiProperties');
	$uploadedArr = $view->getParam('uploadedArr');
	$csvLines = $view->getParam('csvLines');
	
	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('rocket_import_check_title')));
?>
<div class="rocket-panel">
    <h3><?php $html->text('rocket_import_check_assign_title')?></h3>

    <table class="rocket-list">
        <tbody>
            <?php foreach ($assignationMap as $prop1 => $prop2): ?>
                <tr>
                    <td>
                        <?php $html->out($prop1) ?>
                    </td>
                    <td>
                        <?php $html->out($scalarEiProperties[$prop2]->getLabelLStr()) ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<div class="rocket-panel">
    Werden hochgelanden: <?php $html->out(count($csvLines) - count($uploadedArr)) ?>
    Hochgeladen: <?php $html->out(count($uploadedArr)) ?>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
            <?php $html->linkToController(array('execute', $view->getParam('iuId')), $html->getText('rocket_import_execute_label'), array('class' => 'rocket-control-success rocket-important')) ?>
        </li>
    </ul>
</div>