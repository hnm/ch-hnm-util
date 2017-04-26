<?php
	$messageContainer = $view->getParam('messageContainer');
	$view->assert($messageContainer instanceof \n2n\l10n\MessageContainer);

	$view->useTemplate('\rocket\core\view\template.html', array('title' => $view->getL10nText('Confirmation')));
?>
<?php if (count($messageContainer->getAll()) === 0): ?>
	<pre>
		Hello Sir, we would like to confirm to you, that your import has been successful.
		Have a nice day and see you importing again soon
	</pre>
<?php endif ?>

<div id="rocket-page-controls">
	<ul>
		<li>
			<?php $html->linkToController(null, 'finished', array('class' => 'rocket-control-success rocket-important')) ?>
		</li>
	</ul>
</div>