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
namespace n2n\core;


use n2n\core\config\AppConfig;
use n2n\web\http\Request;
use n2n\core\container\N2nContext;
use n2n\web\http\Session;
use n2n\web\http\HttpContext;
use n2n\web\http\Response;
use n2n\web\http\ResponseCacheStore;

class HttpContextFactory {
	
	/**
	 * @param AppConfig $appConfig
	 * @param Request $request
	 * @param N2nContext $n2nContext
	 * @return \n2n\core\HttpContext
	 */
	static function createFromAppConfig(AppConfig $appConfig, Request $request, Session $session, N2nContext $n2nContext) {
		$generalConfig = $appConfig->general();
		$webConfig = $appConfig->web();
		$filesConfig = $appConfig->files();
		
		$response = new Response($request);
		$response->setResponseCachingEnabled($webConfig->isResponseCachingEnabled());
		$response->setResponseCacheStore($n2nContext->lookup(ResponseCacheStore::class));
		$response->setHttpCachingEnabled($webConfig->isResponseBrowserCachingEnabled());
		$response->setSendEtagAllowed($webConfig->isResponseSendEtagAllowed());
		$response->setSendLastModifiedAllowed($webConfig->isResponseSendLastModifiedAllowed());
		$response->setServerPushAllowed($webConfig->isResponseServerPushAllowed());
		
		$assetsUrl = $filesConfig->getAssetsUrl();
		if ($assetsUrl->isRelative() && !$assetsUrl->getPath()->hasLeadingDelimiter()) {
			$assetsUrl = $request->getContextPath()->toUrl()->ext($assetsUrl);
		}
		
		return new HttpContext($request, $response, $session, $assetsUrl,
				$webConfig->getSupersystem(), $webConfig->getSubsystems(), $n2nContext);
	}
}