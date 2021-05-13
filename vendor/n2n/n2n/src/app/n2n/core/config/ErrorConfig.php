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
namespace n2n\core\config;

class ErrorConfig {
	const ERROR_VIEW_DEFAULT_KEY_SUFFIX = 'default';

	private $strictAttitude; 
	private $startupDetectErrors; 
	private $startupDetectBadRequests; 
	private $logSaveDetailInfo; 
	private $logSendMail; 
	private $logMailRecipient; 
	private $logHandleStatusExceptions; 
	private $logExcludedHttpStatuses; 
	private $errorViewNames;

	public function __construct(bool $strictAttitude, bool $startupDetectErrors, bool $startupDetectBadRequests, 
			bool $logSaveDetailInfo, bool $logSendMail, string $logMailRecipient = null, bool $logHandleStatusExceptions, 
			array $logExcludedHttpStatuses, array $errorViewNames) {
		$this->strictAttitude = $strictAttitude;
		$this->startupDetectErrors = $startupDetectErrors;
		$this->startupDetectBadRequests = $startupDetectBadRequests;
		$this->logSaveDetailInfo = $logSaveDetailInfo; 
		$this->logSendMail = $logSendMail;
		$this->logMailRecipient = $logMailRecipient;
		$this->logHandleStatusExceptions = $logHandleStatusExceptions;
		$this->logExcludedHttpStatuses = $logExcludedHttpStatuses;
		$this->errorViewNames = $errorViewNames;
	}
	/**
	 * @return bool
	 */
	public function isStrictAttitudeEnabled(): bool {
		return $this->strictAttitude;
	}
	/**
	 * @return bool
	 */
	public function isDetectStartupErrorsEnabled(): bool {
		return $this->startupDetectErrors;
	}
	/**
	 * @return bool
	 */
	public function isStartupDetectBadRequestsEnabled(): bool {
		return $this->startupDetectBadRequests;
	}
	/**
	 * @return bool
	 */
	public function isLogSaveDetailInfoEnabled(): bool {
		return $this->logSaveDetailInfo;
	}
	/**
	 * @return bool
	 */
	public function isLogSendMailEnabled(): bool {
		return $this->logSendMail;
	}
	/**
	 * @return string
	 */
	public function getLogMailRecipient() {
		return $this->logMailRecipient;
	}
	/**
	 * @return bool
	 */
	public function isLogHandleStatusExceptionsEnabled(): bool {
		return $this->logHandleStatusExceptions;
	}
	/**
	 * 
	 * @return array
	 */
	public function getLogExcludedHttpStatus(): array {
		return $this->logExcludedHttpStatuses;
	}
	/**
	 * @param int $httpStatus
	 * @return bool
	 */
	public function isLoggingForStatusExceptionEnabled($httpStatus): bool {
		return $this->isLogHandleStatusExceptionsEnabled() && !in_array($httpStatus, $this->getLogExcludedHttpStatus());
	}
	/** 
	 * @param int $httpStatus
	 * @return string
	 */
	public function getErrorViewName($httpStatus) {
		if (isset($this->errorViewNames[$httpStatus])) {
			return $this->errorViewNames[$httpStatus];
		}
		
		if (isset($this->errorViewNames[self::ERROR_VIEW_DEFAULT_KEY_SUFFIX])) {
			return $this->errorViewNames[self::ERROR_VIEW_DEFAULT_KEY_SUFFIX];
		}
		
		return null;
	}
	
	public function getErrorViewNames(): array {
		return $this->errorViewNames;
	}
}
