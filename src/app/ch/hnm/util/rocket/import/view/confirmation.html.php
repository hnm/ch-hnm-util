<?php
	use n2n\impl\web\ui\view\html\HtmlView;
	
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);	

	$messageContainer = $view->getParam('messageContainer');
	$view->assert($messageContainer instanceof \n2n\l10n\MessageContainer);

	$title = null;
	if (count($messageContainer->getAll()) > 0) {
        $title = $view->getL10nText('rocket_import_confirmation_title_no_success');
    } else {
		$title = $view->getL10nText('rocket_import_confirmation_title_success');
    }

	$view->useTemplate('\rocket\core\view\template.html', array('title' => $title));
?>
<?php if (count($messageContainer->getAll()) === 0): ?>
	<pre>
		<?php $html->text('rocket_import_confirmation_message') ?>
	</pre>
<?php endif ?>

<div id="rocket-page-controls">
	<ul>
		<li>
			<?php $html->linkToController(null, $html->getText('rocket_import_finished_label'),
                    array('class' => 'rocket-structure-content-success rocket-important')) ?>
		</li>
	</ul>
</div>