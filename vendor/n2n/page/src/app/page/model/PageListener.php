<?php
namespace page\model;

interface PageListener {
	
	public function onPageEvent(PageEvent $pageEvent);
	
	public function onFlush();
}