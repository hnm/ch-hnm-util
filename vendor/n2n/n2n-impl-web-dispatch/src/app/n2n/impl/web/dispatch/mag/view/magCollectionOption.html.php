<?php
	/*
	 * Copyright (c) 2012-2016, Hofmänner New Media.
	 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
	 *
	 * This file is part of the N2N FRAMEWORK.
	 *
	 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
	 * the GNU Lesser General Public License as published by the Free Software Foundation, either
	 * version 2.1 of the License, or (at your option) any later version.
	 *
	 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
	 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
	 *
	 * The following people participated in this project:
	 *
	 * Andreas von Burg.....: Architect, Lead Developer
	 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
	 * Thomas Günther.......: Developer, Hangar
	 */

	use n2n\web\dispatch\map\PropertyPath;
	use n2n\impl\web\ui\view\html\HtmlView;
		
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$formHtml = HtmlView::formHtml($view);
	
	$propertyPath = $view->getParam('propertyPath');
	$view->assert($propertyPath instanceof PropertyPath);
?>

<ul class="n2n-option-collection">
	<?php $formHtml->meta()->objectProps($propertyPath, function () use ($view, $formHtml) { ?>
		<?php $formHtml->magOpen('li') ?>
			<?php $formHtml->magLabel() ?>
			<?php $view->out('<div>')?>
				<?php $formHtml->magField() ?>
			<?php $view->out('</div>')?>
		<?php $formHtml->magClose() ?>
	<?php }) ?>
</ul>