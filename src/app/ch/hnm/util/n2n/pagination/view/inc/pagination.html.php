<?php
	use ch\hnm\util\n2n\pagination\model\Pagination;
	use n2n\impl\web\ui\view\html\HtmlView;
	
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$request = HtmlView::request($view);
	
	$pagination = $view->getParam('pagination');
	if (null === $pagination) return;
	$view->assert($pagination instanceof Pagination);
	$currentPageNum = $pagination->getCurrentPageNum();
	$numPages = $pagination->getNumPages();
	if ($numPages <= 1) return;
	
	$numVisiblePaginationEntries = $pagination->getNumVisiblePaginationEntries();
	
	$firstPageNum = $currentPageNum - floor($numVisiblePaginationEntries / 2);
	
	$fromPage = ($firstPageNum < 1) ? 1 : $firstPageNum;
	$lastPageNum = $fromPage + $numVisiblePaginationEntries - 1;
	if ($lastPageNum > $numPages) {
		$firstPageNum = $firstPageNum - ($lastPageNum - $numPages);
		$fromPage = ($firstPageNum < 1) ? 1 : $firstPageNum;
	}
	$toPage = ($lastPageNum > $numPages) ? $numPages : $lastPageNum;
?>
<nav aria-label="Page navigation example">
<ul class="<?php $html->out($pagination->getPaginationClassName()) ?>">
	<li class="page-item<?php $html->out($currentPageNum > 1 ? null : ' disabled')?>">
		<?php $html->link($pagination->getPath($request, $currentPageNum - 1), $pagination->getPreviousLabel(), array('class' => 'page-link', 'tabindex' => $currentPageNum > 1 ? null : '-1')) ?>
	</li>
	<?php if ($pagination->showFirst() && ($firstPageNum > 1)) : ?>
		<li class="page-item">
			<?php $html->link($pagination->getPath($request, 1), 1, array('class' => 'page-link')) ?>
		</li>
		<?php if ($firstPageNum > 2) : ?>
			<li class="page-item">
				<span class="page-link"><?php $html->out($pagination->getDivider()) ?></span>
			</li>
		<?php endif ?>
	<?php endif?>
	
	<?php for ($i = $fromPage; $i <= $toPage; $i++) : ?>
		<?php if ($currentPageNum != $i ) : ?>
			<li class="page-item">
				<?php $html->link($pagination->getPath($request, $i), $i, array('class' => 'page-link')) ?>
			</li>
		<?php else : ?>
			<li class="page-item <?php $html->out($pagination->getActiveClassName()) ?>">
				<span class="page-link"><?php $html->out($i) ?><span class="sr-only"><?php $html->text('pagination_current_page') ?></span></span>
 			</li>
		<?php endif ?>
	<?php endfor ?>
	<?php if ($pagination->showLast() && $lastPageNum < $numPages) : ?>
		<?php if ($lastPageNum < $numPages - 1) : ?>
			<li class="page-item">
				<span class="page-link"><?php $html->out($pagination->getDivider()) ?></span>
			</li>
		<?php endif ?>
		<li>
			<?php $html->link($pagination->getPath($request, $numPages),  $numPages, array('class' => 'page-link')) ?>
		</li>
	<?php endif?>
	<li class="page-item<?php $html->out($currentPageNum !== $numPages ? null : ' disabled') ?>">
		<?php $html->link($pagination->getPath($request, $currentPageNum + 1), $pagination->getNextLabel(), array('class' => 'page-link', 'tabindex' => $currentPageNum !== $numPages ? null : '-1')) ?>
	</li>
</ul>
</nav>