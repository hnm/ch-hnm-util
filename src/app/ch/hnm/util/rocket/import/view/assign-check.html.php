<?php
use rocket\spec\ei\manage\generic\CommonScalarEiProperty;

$assignationMap = $view->getParam('assignationMap');

$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Assert')));
?>
<div class="rocket-panel">
    <h3>Assign</h3>

    <table class="rocket-list">
        <tbody>
            <?php foreach ($assignationMap as $prop1 => $prop2): ?>
                <tr>
                    <td>
                        <?php $html->out($prop1) ?>
                    </td>
                    <td>
                        <?php $html->out($prop2) ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<div id="rocket-page-controls">
    <ul>
        <li>
            <?php $html->linkToController(array('execute', $view->getParam('iuId')), 'Continue', array('class' => 'rocket-control-success rocket-important')) ?>
        </li>
    </ul>
</div>