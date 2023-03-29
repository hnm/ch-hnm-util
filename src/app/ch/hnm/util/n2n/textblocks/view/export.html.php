<?php
	use ch\hnm\util\n2n\textblocks\model\TextBlockExportForm;
	use n2n\impl\web\ui\view\html\HtmlView;
	use n2n\l10n\Message;
	
	$view = HtmlView::view($this);
	$html = HtmlView::html($view);
	$formHtml = HtmlView::formHtml($view);

	$textBlockExportForm = $view->getParam('textBlockExportForm');
	$view->assert($textBlockExportForm instanceof TextBlockExportForm);
	
	try {
		$view->useTemplate('\bstmpl\view\bsTemplate.html', array('title' => 'Textblocks'));
	} catch (\ReflectionException $e) {
		$view->useTemplate('template.html', ['title' => 'Textblocks']);
	}
	
// 	$bootstrapFormHtml = new BsFormHtmlBuilder($view);
?>
<?php $html->messageList(null, Message::SEVERITY_INFO, array('class' => 'alert alert-info list-unstyled')) ?>
<?php $formHtml->open($textBlockExportForm, null, null, array('class' => 'form-horizontal')) ?>
	<div class="form-group">
		<h2>Select Modules</h2>
		<div class="">
			<?php foreach ($textBlockExportForm->getModuleNamespaceOptions() as $moduleNamespace => $moduleName): ?>
				<label class="checkbox">
					<?php $formHtml->inputCheckbox('selectedModuleNamespaces[' . $moduleNamespace . ']', $moduleNamespace) ?>
					<?php $html->out($moduleNamespace) ?>
				</label>
			<?php endforeach ?>
		</div>
	</div>
	<div class="form-group">
		<h2>
			Select Locales
		</h2>
		<div class="">
			<?php foreach (TextBlockExportForm::getLocaleIdOptions() as $localeId => $localeName): ?>
				<label class="checkbox">
					<?php $formHtml->inputCheckbox('selectedLocaleIds[' . $localeId . ']', $localeId) ?>
					<?php $html->out($localeName) ?>
				</label>
			<?php endforeach ?>
		</div>
	</div>
	<div class="form-group">
		<h2>
			Existing Language Keys
		</h2>
		<label class="radio">
			<?php $formHtml->inputRadio('skipTranslated', true) ?>
			Skip existing Language Keys
		</label>
		<label class="radio">
			<?php $formHtml->inputRadio('skipTranslated', false) ?>
			Print Translations 
		</label>
	</div>
	<div>
		<?php $formHtml->inputSubmit('export', 'Language Files Exportieren', array('class' => 'btn btn-block')) ?>
	</div>
<?php $formHtml->close() ?>
