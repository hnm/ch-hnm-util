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
namespace n2n\core\err;

use n2n\log4php\Logger;
use n2n\web\http\StatusException;
use n2n\persistence\PdoPreparedExecutionException;
use n2n\web\http\Response;
use n2n\core\N2N;
use n2n\io\IoUtils;
use n2n\web\http\BadRequestException;
use n2n\util\ex\QueryStumble;
use n2n\core\TypeLoader;
use n2n\web\http\controller\ControllerContext;
use n2n\web\ui\ViewFactory;
use n2n\io\IoException;
use n2n\util\type\TypeUtils;

// define('N2N_EXCEPTION_HANDLING_PHP_SEVERITIES', E_ALL | E_STRICT);
// define('N2N_EXCEPTION_HANDLING_PHP_STRICT_ATTITUTE_SEVERITIES', E_STRICT | E_WARNING | E_NOTICE | E_CORE_WARNING | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED);

require_once 'ThrowableInfoFactory.php';

/**
 * @author Andreas von Burg
 */
class ExceptionHandler {
	const HANDLED_PHP_SEVERITIES = E_ALL | E_STRICT;
	const STRICT_ATTITUTE_PHP_SEVERITIES = E_STRICT | E_WARNING | E_NOTICE | E_CORE_WARNING | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED;
	
	const LOG_FILE_EXTENSION = '.log';
	const DEFAULT_500_DEV_VIEW = 'n2n\core\view\errorpages\500Dev.html';
	const DEFAULT_STATUS_DEV_VIEW = 'n2n\core\view\errorpages\statusDev.html';
	const DEFAULT_STATUS_LIVE_VIEW = 'n2n\core\view\errorpages\statusLive.html';
	
	private $developmentMode;
	
	private $dispatchingThrowables = array();
	private $loggedExceptions = 0;
	private $errorHashes = array();
	private $stable = true;
	private $pendingLogException = array();
	private $httpFatalExceptionSent = false;
	private $pendingOutputs = array();

	private $strictAttitude = false;
	private $logDetailDirPath;
	private $logDetailFilePerm;
	private $logMailRecipient;
	private $logMailAddresser;
	private $logMailBufferDirPath;
	private $logStatusExceptionsEnabled = true;
	private $logExcludedHttpStatus = array();
	private $logger;
	
	private $detectStartupErrorsEnabled = true;
	private $detectBadRequestsOnStartupEnabled = true;
	private $prevError;
	/**
	 * 
	 * @param bool $developmentMode
	 */
	public function __construct(bool $developmentMode) {
		$this->developmentMode = $developmentMode;
		ini_set('display_errors', (int) $this->developmentMode);

		error_reporting(self::HANDLED_PHP_SEVERITIES);

		set_error_handler(array($this, 'handleTriggeredError'), self::HANDLED_PHP_SEVERITIES);
		set_exception_handler(array($this, 'handleThrowable'));
		
		$this->prevError = error_get_last();
	}
	
	public function setDetectStartupErrorsEnabled(bool $detectStartupErrorsEnabled) {
		$this->detectStartupErrorsEnabled = $detectStartupErrorsEnabled;
	}
	
	public function isDetectStartupErrorsEnabled(): bool {
		return $this->detectStartupErrorsEnabled;
	}
	
	public function setDetectBadRequestsOnStartupEnabled(bool $detectBadRequestsOnStartup) {
		$this->detectBadRequestsOnStartupEnabled = $detectBadRequestsOnStartup;
	}
	
	public function isDetectBadRequestsOnStartupEnabled(): bool {
		return $this->detectBadRequestsOnStartupEnabled;
	}
	
	/**
	 * Method do enabled or disable strict exception handling. If strict
	 * attitude is enabled php warnings will cause exceptions
	 * 
	 * @param bool $strictAttitude if true strictAttitude is enabled
	 */
	public function setStrictAttitude(bool $strictAttitude) {
		$this->strictAttitude = $strictAttitude;
	}
	
	/**
	 * @return boolean
	 */
	public function isStrictAttitude(): bool {
		return $this->strictAttitude;
	}
	
	/**
	 * If dirPath and filePerm are set, the exception handler creates a 
	 * detailed info file about every exception handled by this exception
	 * handler.
	 * 
	 * @param string $dirPath directory within the info files are created
	 * @param string $filePerm file permissions to use for new info files
	 */
	public function setLogDetailDirPath($dirPath, $filePerm) {
		$this->logDetailDirPath = $dirPath;
		$this->logDetailFilePerm = $filePerm;
	}
	/**
	 * If mailRecipient and mailAddresser are set, the exception handler
	 * sends an mail when an exception was handled by this exception handler. 
	 * 
	 * @param string $mailRecipient
	 * @param string $mailAddresser
	 */
	public function setLogMailRecipient(string $mailRecipient, string $mailAddresser) {
		$this->logMailRecipient = $mailRecipient;
		$this->logMailAddresser = $mailAddresser;
	}
	
	/**
	 * @param string $logMailBufferDirPath
	 */
	public function setLogMailBufferDirPath(string $logMailBufferDirPath) {
		$this->logMailBufferDirPath = $logMailBufferDirPath;
	}
	
	public function setLogStatusExceptionsEnabled($logStatusExceptionsEnabled, array $logExcludedHttpStatus) {
		$this->logStatusExceptionsEnabled = $logStatusExceptionsEnabled;
		$this->logExcludedHttpStatus = $logExcludedHttpStatus;
	}
	
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
	}
	/**
	 * <p>Tells you whether or not an excpetion is occurred and a exception 
	 * screen was rendered.</p>
	 * 
	 * <p>NOTICE: If a warning or notice was triggered and strict attitude is 
	 * disabled this method returns false</p>
	 *
	 * @return boolean true if exception had to be handled by the excpetion handler. 
	 */
	public function errorOccurred() {
		return (bool) sizeof($this->dispatchingThrowables);
	}
	
	/**
	 * If stable is false the exception hanlder wasn't able to use the N2N api 
	 * due to a fatal error and had to access the ob buffer in a dirty way.
	 * 
	 * @return boolean true if stable
	 */
	public function isStable() {
		return $this->stable;	
	}
	
	private $ignoredNextTriggeredErrNo = 0;
	private $ignoredErrorMessage = null;
	
	public function ignoreNextTriggeredErrNo(int $errNo) {
		$this->ignoredNextTriggeredErrNo = $errNo;
		$this->ignoredErrorMessage = null;
	}
	
	public function getIgnoredErrorMessage() {
		return $this->ignoredErrorMessage;
	} 
	
	/**
	 * <p>Will be registered as php error_handler while ExceptionHandler initialization
	 * {@link http://php.net/manual/de/function.set-error-handler.php}</p>
	 *
	 * <p>This method should nerver be called directly.</p>
	 *
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @throws \Error
	 * @return boolean
	 */
	public function handleTriggeredError($errno, $errstr, $errfile, $errline, $errcontext = null, $forceThrow = false) {
		$this->registerError($errno, $errfile, $errline, $errstr);
		$ignoredNextTriggeredErrNo = $this->ignoredNextTriggeredErrNo;
		$this->ignoredNextTriggeredErrNo = 0;
		
		if (!$forceThrow && $ignoredNextTriggeredErrNo & $errno) {
			$this->ignoredErrorMessage = $errstr;
			return true;
		}

		if ($this->isMemoryLimitExhaustedMessage($errstr)) {
			// @todo find out if dangerous
			//$this->stable = false;
		} else {
			// @ --> error_reporting() == false
			if (!$forceThrow && !error_reporting()) return false;
		}
		
		$e = $this->createPhpError($errno, $errstr, $errfile, $errline);
		$e = $this->checkForTypeLoaderThrowable($e);
	
		$this->log($e);
		
		if ($forceThrow || $this->strictAttitude || !($errno & self::STRICT_ATTITUTE_PHP_SEVERITIES)) {
			throw $e;
		}
	
		return false;
	}
	
	
	private function checkForTypeLoaderThrowable(\Throwable $throwable) {
		if (null !== ($tle = TypeLoader::getLatestException())) {
			TypeLoader::clear();

			if ($throwable instanceof \Error && ($throwable->getCode() === 0 || $throwable->getCode() === E_ERROR)
					&& $throwable->getFile() === $tle->getFile() && $throwable->getLine() === $tle->getLine()) {
				return $tle;
			}
		}
		
		return $throwable;
	}
	/**
	 * Will be registered as php exception_handler while ExceptionHandler initialization
	 * @see http://php.net/manual/de/function.set-exception-handler.php
	 *
	 * @param \Throwable $throwable
	 * @param bool $dispatchException if true response will be reseted and an exception
	 * 		view will be shown
	 */
	public function handleThrowable(\Throwable $throwable, $logException = true, $dispatchException = true) {
		$throwable = $this->checkForTypeLoaderThrowable($throwable);
		
		if ($logException) {
			$this->log($throwable);
		}
		if ($dispatchException) {
			$this->dispatchException($throwable);
		}
		$this->checkForPendingLogExceptions();
	}
	
	public function checkForStartupErrors() {
		if (null === $this->prevError) {
			return;
		}
		
		if ($this->detectBadRequestsOnStartupEnabled && $this->isPrevBadRequestMessage($this->prevError['message'])) {
			$this->registerError($this->prevError['type'], $this->prevError['file'], 
					$this->prevError['line'], $this->prevError['message']);
			$e = $this->createPhpError($this->prevError['type'], $this->prevError['message'], 
					$this->prevError['file'], $this->prevError['line']);
			throw new BadRequestException('Php prev error deteced: ' . $e->getMessage());
		}
		
		if ($this->detectStartupErrorsEnabled) {
			$this->handleTriggeredError($this->prevError['type'], $this->prevError['message'], 
					$this->prevError['file'], $this->prevError['line']);
		} else {
			$this->registerError($this->prevError['type'], $this->prevError['file'],
					$this->prevError['line'], $this->prevError['message']);
		}
	}
	/**
	 * Is called from N2N after shutdown was performed to detect and handle fatal errors which
	 * weren't handled yet.
	 */
	public function checkForFatalErrors() {
		// in case of memory size exhausted error
		gc_collect_cycles();
// 		$this->checkForPendingLogExceptions();
		
		$error = error_get_last();
		
		if (!isset($error) || $this->checkForError($error['type'], $error['file'], $error['line'], $error['message'])) {
			return;
		}
				
// 		if ($error['type'] == E_WARNING && substr($error['message'], 0, 23) == 'DateTime::__construct()') {
// 			return;
// 		}
		
// 		if (!$this->strictAttitude || ($error['type'] & self::STRICT_ATTITUTE_PHP_SEVERITIES)) {
// 			return;
// 		}
		
		try {
			$this->handleTriggeredError($error['type'], $error['message'], $error['file'], $error['line'], null, true);
		} catch (\Throwable $e) {
			$this->dispatchException($e);
		}

		$this->checkForPendingLogExceptions();
	}
	/**
	 * Possible exceptions which were thrown while logging will be handled. 
	 */
	private function checkForPendingLogExceptions() {
		foreach ($this->pendingLogException as $logException) {
			$this->log($logException);
			$this->dispatchException($logException);
		}
		$this->pendingLogException = array();
	}
	
	/**
	 * Creates an exception based on a triggered error
	 * 
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @return \Error created exception
	 */
	private function createPhpError($errno, $errstr, $errfile, $errline) {
		switch($errno) {
			case E_ERROR:
// 				if (!is_null($exception = $this->checkForTypeLoaderException($errno, $errstr, $errfile, $errline))) {
// 					return $exception;
// 				}
			case E_USER_ERROR:
			case E_COMPILE_ERROR:
			case E_CORE_ERROR:
				return new FatalError($errstr, $errno, $errfile, $errline);
			case E_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
			case E_CORE_WARNING:
				return new WarningError($errstr, $errno, $errfile, $errline);
			case E_NOTICE:
			case E_USER_NOTICE:
				return new NoticeError($errstr, $errno, $errfile, $errline);
			case E_RECOVERABLE_ERROR:
				return new RecoverableError($errstr, $errno, $errfile, $errline);
			case E_STRICT:
				return new StrictError($errstr, $errno, $errfile, $errline);
			case E_PARSE:
				return new ParseError($errstr, $errno, $errfile, $errline);
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				return new DeprecatedError($errstr, $errno, $errfile, $errline);
			default:
				return new FatalError($errstr, $errno, $errfile, $errline);
		}
	}
	
	
	/**
	 * If the passed error was triggered because of an autoloading error,
	 * this method returns the correspondent TypeLoaderException.
	 * 
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @return TypeLoaderErrorException null if triggered error was not caused 
	 *		 by autoloading
	 */
// 	private function checkForTypeLoaderException($errno, $errstr, $errfile, $errline) {
// 		$loaderException = null;
		
// 		if (!class_exists('n2n\core\TypeLoader', false) || is_null($loaderException = TypeLoader::getLatestException())) {
// 			return null;	
// 		}
		
// 		if ($loaderException->getFile() != $errfile || $loaderException->getLine() != $errline) {
// 			return null;
// 		}
		
// 		if (!preg_match('/(?<=\')[^\']+(?=\' not found)/', $errstr, $matches)) {
// 			return null;
// 		}
		
// 		if ($matches[0] != $loaderException->getTypeName()) {
// 			return null;
// 		}
		
// 		return $loaderException;
// 	}
// 	/**
// 	 * Makes error infos more user friendly  
// 	 * 
// 	 * @param string $errstr
// 	 * @param string $errfile
// 	 * @param string $errline
// 	 */
// 	private function prepareMessage(&$errstr, &$errfile, &$errline) {
// 		$fmatches = array();
// 		$lmatches = array();

// 		$errstr .= " in [strong]" . $errfile . "[/strong] on line [strong]" . $errline ."[/strong]";

// 		if (is_numeric(strpos($errstr, '{closure}'))) {
// 			return;
// 		}
		
// 		if (preg_match('/(?<=called in )[^ \(]+/', $errstr, $fmatches)
// 				&& preg_match('/((?<=on line )|(?<=\())[0-9]+/', $errstr, $lmatches)) {
// 			$errfile = $fmatches[0];
// 			$errline = $lmatches[0];
// 		}
// 	}
	
	const MEMORY_LIMIT_EXHAUSTED_ERROR_MSG = 'Allowed memory size of';
	
	private function isMemoryLimitExhaustedMessage($message) {
		return self::MEMORY_LIMIT_EXHAUSTED_ERROR_MSG == substr($message, 0, 
				strlen(self::MEMORY_LIMIT_EXHAUSTED_ERROR_MSG));
	}
	
	
	// 	Warning: POST Content-Length of 60582676 bytes exceeds the limit of 8388608 bytes in Unknown on line 0
	const POST_LENGTH_ERROR_MSG_PREFIX = 'POST Content-Length';
	// 	Warning: Maximum number of allowable file uploads has been exceeded in Unknown on line 0
	const UPLOAD_NUM_ERROR_MSG_PREFIX = 'Maximum number';
	// Warning: Unknown: Input variables exceeded 2. To increase the limit change max_input_vars in php.ini. in Unknown on line 0
	const INPUT_VARS_NUM_ERROR_MSG_PREFIX = 'Unknown: Input variables exceeded';
	
	private function isPrevBadRequestMessage($message) {
		return self::POST_LENGTH_ERROR_MSG_PREFIX == substr($message, 0, strlen(self::POST_LENGTH_ERROR_MSG_PREFIX))
				|| self::UPLOAD_NUM_ERROR_MSG_PREFIX == substr($message, 0, strlen(self::UPLOAD_NUM_ERROR_MSG_PREFIX))
				|| self::INPUT_VARS_NUM_ERROR_MSG_PREFIX == substr($message, 0, strlen(self::INPUT_VARS_NUM_ERROR_MSG_PREFIX));
	}
	/**
	 * An Error Hash is a unique hash of an triggered error.
	 *	
	 * @param int $errno
	 * @param string $errfile
	 * @param int $errline
	 * @param string $errstr
	 * @return string
	 */
	private function buildErrorHash($errno, $errfile, $errline, $errstr) {
		return sha1($errno . '-' . $errfile . '-' . $errline . '-' . $errstr);
	}
	
	private function registerError($errno, $errfile, $errline, $errstr) {
		$this->errorHashes[] = $this->buildErrorHash($errno, $errfile, $errline, $errstr);
	}
	
	private function checkForError($errno, $errfile, $errline, $errstr) {
		return in_array($this->buildErrorHash($errno, $errfile, $errline, $errstr), $this->errorHashes);
	}
	/**
	 * Logs the passed Exception. This includes a mail and error info file if it is enabled in 
	 * the app.ini. A log entry is also sent to log4php. If logging failed this method must throw 
	 * any exceptions. Exceptions must be registered in the 
	 * {@link ExceptionHandler::$pendingLogExceptions} property.
	 * 
	 * @param \Throwable $e
	 */
	private function log(\Throwable $e) {
		if ($e instanceof StatusException && (!$this->logStatusExceptionsEnabled 
				|| in_array($e->getStatus(), $this->logExcludedHttpStatus))) {
			return;
		}
		
		$simpleMessage = $this->createSimpleLogMessage($e);
		error_log($simpleMessage, 0);
		
		if (isset($this->logDetailDirPath) || isset($this->logMailRecipient)) {
			$detailMessage = $this->createDetailLogMessage($e);
			
			if (isset($this->logMailRecipient)) {
				$this->sendLogMail($e, $detailMessage);
			}
			
			if (isset($this->logDetailDirPath)) {
				$defLogBasePath = $this->logDetailDirPath . DIRECTORY_SEPARATOR  . date('Y-m-d_His') . str_replace('\\', '_', get_class($e));
				$ext = '';
				for ($i = 0; is_file($defLogBasePath . $ext . self::LOG_FILE_EXTENSION); $i++) {
					$ext = '_' . $i;
				}
				
				$defLogPath = $defLogBasePath . $ext . self::LOG_FILE_EXTENSION;
				try {
					IoUtils::putContents($defLogPath, $detailMessage);
					IoUtils::chmod($defLogPath, $this->logDetailFilePerm);
				} catch (\Exception $e) {
					$logE = $this->createLoggingFailedException($e);;
					$this->pendingLogException[spl_object_hash($logE)] = $logE;
				}
			}
		}
		// cannot log deprecated exception because class loader cant be called anymore if 
		// "Deprecated: Call-time pass-by-reference has been deprecated"-Warning occoures.
		if (isset($this->logger) && $this->stable
				/*&& !($e instanceof PHPDeprecatedException && $e->getSeverity() == E_DEPRECATED)
				&& !($e instanceof PHPStrictException && $e->getSeverity() == E_STRICT)*/) {
			try {
				$this->logger->error($simpleMessage, $e);
			} catch (\Throwable $e) {
				$logE = $this->createLoggingFailedException($e);
				$this->pendingLogException[spl_object_hash($logE)] = $logE;
			}
		}
	}
	
	private function sendLogMail(\Throwable $e, string $detailMessage) {
		$times = 1;
		if ($this->logMailBufferDirPath !== null) {
			$logMailBuffer = new LogMailBuffer($this->logMailBufferDirPath);
			$times = $logMailBuffer->check($e);
			
			if ($times == 0) return;
		}
		
		$header = 'From: ' . $this->logMailAddresser . "\r\n" .
				'Reply-To: ' . $this->logMailAddresser  . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
		
		$subject = get_class($e) . ' occurred';
		if ($times > 1) {
			$subject .= ' ' . $times . ' times';
		}
		
		@mail($this->logMailRecipient, $subject, $detailMessage, $header);
	}
	
	private function createLoggingFailedException(\Throwable $reasonE) {
		throw new LoggingFailedException('Exception logging failed.', 0, $reasonE);
	}
	/**
	 * Creates short description of an exception used for logging
	 * 
	 * @param \Exception $e
	 * @return string short description
	 */
	private function createSimpleLogMessage(\Throwable $e) {
		$message = get_class($e) . ': ' . $e->getMessage();
		if ($e instanceof \ErrorException || $e instanceof \Error) {
			$message .= ' in ' . $e->getFile() . ' on line ' . $e->getLine();
		}
		return $message;
	}
	/**
	 * Creates a detailed description of an exception which is used for exception mails,
	 * exception info files and fatal exception views
	 * 
	 * @param \Exception $e
	 * @return string detailed description
	 */
	private function createDetailLogMessage(\Throwable $e) {		
		// build title
		$eName = get_class($e);
		$title = 'An ' . $eName . ' occurred';
		$debugContent =  $title . PHP_EOL .
				str_repeat('+', mb_strlen($title)) . PHP_EOL . PHP_EOL;
		$debugContent .= $e->getMessage() . PHP_EOL . PHP_EOL;
		
		if ($e instanceof \ErrorException || $e instanceof \Error) {
			$debugContent .= 'File: ' . $e->getFile() . PHP_EOL.
					'Line: ' . $e->getLine()  . PHP_EOL . PHP_EOL;
		}
			
		// build query info for PDOExceptions
		if ($e instanceof QueryStumble) {
			$debugContent .= 'STATEMENT' . PHP_EOL
					. '---------' . PHP_EOL;
			$debugContent .= $e->getQueryString() . PHP_EOL . PHP_EOL;
			
			if ($e instanceof PdoPreparedExecutionException) {
				$boundValuesStr = "";
				foreach($e->getBoundValues() as $name => $value) {
					if (!mb_strlen($boundValuesStr)) $boundValuesStr .= ', ';
					$boundValuesStr .= $name . '=' . TypeUtils::buildScalar($value) . PHP_EOL;
				}
				$debugContent .= 'Bound values: ' . $boundValuesStr . PHP_EOL;
			}
		}
		
		// build stack trace
		$debugContent .= 'STACK TRACE' . PHP_EOL
				. '-----------' . PHP_EOL;
		$debugContent .= $eName . ': ' . $e->getTraceAsString() . PHP_EOL;
		
		$curE = $e;
		while (null != ($curE = $curE->getPrevious())) {
			$debugContent .= PHP_EOL . get_class($curE) . ': ' . $this->createSimpleLogMessage($curE) . PHP_EOL 
					. $curE->getTraceAsString() . PHP_EOL;
		}
		$debugContent .= PHP_EOL;
		
		if (isset($_SERVER['REQUEST_URI'])) {
			// build http request
			$debugContent .= 'HTTP REQUEST' . PHP_EOL
					. '------------' . PHP_EOL;
			$debugContent .= $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
			
			foreach($_SERVER as $name => $value) {
				if (stristr($name, 'HTTP_')) {
					$debugContent .= substr($name, 5) . "	" . $value . PHP_EOL;
				}
			}
		}
		
		if (!$this->stable) {
			$debugContent .= 'VARS CANNOT BE DISPLAYED DUE TO UNSTABLE PHP STATE';
		} else {
			$debugContent .= $this->createLogArrayStr('SERVER VARS', $_SERVER);
	
			if (!empty($_GET)) {
				$debugContent .= $this->createLogArrayStr('HTTP GET VARS', $_GET);
			}
			
			if (!empty($_POST)) {
				$debugContent .= $this->createLogArrayStr('HTTP POST VARS', $_POST);
			}
			
			if (!empty($_COOKIE)) {
				$debugContent .= $this->createLogArrayStr('HTTP COOKIE VARS', $_COOKIE);
			}
		}
		
		return $debugContent;
	}
	
	private function createLogArrayStr($title, array $arr) {
		$debugContent = PHP_EOL . $title . PHP_EOL
				. str_repeat('-', mb_strlen($title)) . PHP_EOL;

		$debugContent .= print_r($arr, true);
		
		return $debugContent;
	}
	/**
	 * Decides what exception infos should be rendered. 
	 * 
	 * @param \Throwable $t
	 */
	private function dispatchException(\Throwable $t) {
		array_unshift($this->dispatchingThrowables, $t);
		$numDispatchingThrowables = sizeof($this->dispatchingThrowables);
		
		if (!N2N::isInitialized() || 2 < $numDispatchingThrowables || (2 == $numDispatchingThrowables 
				&& !($this->dispatchingThrowables[1] instanceof StatusException)) || !$this->stable) {
			if (!isset($_SERVER['HTTP_HOST'])) {
				$this->renderExceptionConsoleInfo($t);
				return;
			}
			
			$this->renderFatalExceptionsHtmlInfo($this->dispatchingThrowables);
			return;
		}
		
		if (!N2N::isHttpContextAvailable()) {
			$this->renderExceptionConsoleInfo($t);
			return;
		}

		try {
			$this->renderBeautifulExceptionView($t);
		} catch (\Throwable $t) {
			$this->handleThrowable($t);
		}
	}
	/**
	 * prints fatal exception infos in html 
	 * 
	 * @param array $exceptions
	 */
	private function renderFatalExceptionsHtmlInfo(array $exceptions) {
		if ($this->httpFatalExceptionSent) return;
		if (!$this->isObActive()) {
			$this->httpFatalExceptionSent = true;
		}
		$output = $this->fetchObDirty();

		if (!headers_sent()) {
			header('Content-Type: text/html; charset=utf-8');
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		}
		
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" 
				. '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\r\n"
				. '<html xmlns="http://www.w3.org/1999/xhtml" lang="de">' . "\r\n"
				. '<head>' . "\r\n" 
				. '<title>Fatal Error occurred</title>' . "\r\n" 
				. '</head>' . "\r\n" 
				. '<body>' . "\r\n"
				. '<h1>Fatal Error occurred</h1>' . "\r\n";
		
		if (N2N::isLiveStageOn()) {
			echo '<p>Webmaster was informed. Please try later again.</p>' . "\r\n";
		} else {
			$i = 0;
			foreach ($exceptions as $e) {
				if ($i++ == 0) {
					echo '<h2>' . htmlspecialchars(get_class($e), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</h2>' . "\r\n"
							. $this->buildDevelopmentExceptionHtmlContent($e);
					continue;
				}
				
				echo '<h2>caused by: ' . htmlspecialchars(get_class($e), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</h2>' . "\r\n" 
						. $this->buildDevelopmentExceptionHtmlContent($e);
			}
			
			echo '<h2>Output</h2>' . "\r\n" .
					'<pre>' . htmlspecialchars($output, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</pre>'  . "\r\n";
		}
		
		echo '</body>' . "\r\n" .
				'</html>';
	}
	/**
	 * prints exception infos n2n was executed on the console.
	 * @param \Throwable $t
	 */
	private function renderExceptionConsoleInfo(\Throwable $t) {
		if (!N2N::isLiveStageOn()) {
			echo PHP_EOL . $this->createDetailLogMessage($t);
			return;
		}
	
		print "error occurred";
	}
	/**
	 * Sends a nice detailed view to the Response
	 * 
	 * @param \Exception $e
	 */
	private function renderBeautifulExceptionView(\Throwable $e) {
		$request = N2N::getCurrentRequest();
		$response = N2N::getCurrentResponse();
		$status = null;
		$viewName = null;
		if ($e instanceof StatusException) {
			$status = $e->getStatus();
		} else {
			$status = Response::STATUS_500_INTERNAL_SERVER_ERROR;
		}
		
		$throwableModel = null;
// 		if ($e instanceof StatusException && isset($viewName)) {
// 			$throwableModel = new ThrowableModel($e, null);
// 		} else {
			$throwableModel = new ThrowableModel($e);
			$this->pendingOutputs[] = $response->fetchBufferedOutput(false);
			$that = $this;
			$throwableModel->setOutputCallback(function () use ($that) {
				$output = implode('', $this->pendingOutputs);
				$this->pendingOutputs = array();
				return $output;
			});
// 		}

		
		$viewName = N2N::getAppConfig()->error()->getErrorViewName($status);
			
		if ($viewName === null) {
			if (!N2N::isDevelopmentModeOn()) {
				$viewName = self::DEFAULT_STATUS_LIVE_VIEW;
			} else {
				if ($status == Response::STATUS_500_INTERNAL_SERVER_ERROR) {
					$viewName = self::DEFAULT_500_DEV_VIEW;
				} else {
					$viewName = self::DEFAULT_STATUS_DEV_VIEW;
				}
			}	
		}
		
		$view = N2N::getN2nContext()->lookup(ViewFactory::class)->create($viewName, array('throwableModel' => $throwableModel));
		$view->setControllerContext(new ControllerContext($request->getCmdPath(), $request->getCmdContextPath()));
		$response->reset();
		$response->setStatus($status);
		if ($e instanceof StatusException) {
			$e->prepareResponse($response);
		}
		$response->send($view);
	}
	/**
	 * Create html description of an exception for fatal error view if development
	 * is enabled.
	 *
	 * @param \Exception $e
	 * @return string html description
	 */
	private function buildDevelopmentExceptionHtmlContent(\Throwable $e) {
		$html = '';
		$first = true;
		do {
			if ($first) $first = false;
			else {
				$html .= '<h3>caused by: ' . htmlspecialchars(get_class($e), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</h3>' . "\r\n";
			}
			
			$html .= '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</p>' . "\r\n";
			
			if ($e instanceof \ErrorException || $e instanceof \Error) {
				$filePath = $e->getFile();
				$lineNo = $e->getLine();
				ThrowableInfoFactory::findCallPos($e, $filePath, $lineNo);
				
				if ($filePath !== null) {
					$html .= '<p>File: <strong>' . htmlspecialchars($filePath, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</strong></p>' . "\r\n";
				}
				
				if ($lineNo !== null) {
					$html .= '<p>Line: <strong>' . htmlspecialchars($lineNo, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</strong></p>' . "\r\n";
				}
			}	
			
			$html .= '<pre>' .  htmlspecialchars(ThrowableInfoFactory::buildStackTraceString($e), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE) . '</pre>' . "\r\n";
		} while (null != ($e = $e->getPrevious()));
			
		return $html;
	}
	/**
	 * @return bool
	 */
	private function isObActive() {
		return 0 < ob_get_level();
	}
	/**
	 * @return string
	 */
	private function fetchObDirty() {
		$this->stable = false;
		$output = implode('', $this->pendingOutputs);
		$this->pendingOutputs = array();
		
		$numObLevels = ob_get_level();
		if ($numObLevels) $output = '';

		for($i = 0; $i < $numObLevels; $i++) {
			$output = ob_get_contents() . $output;

			if ($i + 1 < $numObLevels) {
				ob_end_clean();
			} else {
				ob_clean();
			}
		}
		return $output;
	}
}

class LogMailBuffer {
	const MAIL_BUFFER_FILE = 'mail-buffer.json';
	
	private $filePath;
	private $data = array();
	
	function __construct(string $dirPath) {
		if (!is_writable($dirPath)) {
			throw new IoException('No access to log mail buffer file: ' . $dirPath);
		}
		
		$this->filePath = $dirPath . DIRECTORY_SEPARATOR . self::MAIL_BUFFER_FILE;
	
		if (@file_exists($this->filePath)) {
			$arr = @json_decode(@file_get_contents($this->filePath), true);
			if (!empty($arr) && is_array($arr)) {
				$this->data = $arr;
			}
		}
		
		if (!isset($this->data['throwableInfos']) || !is_array($this->data['throwableInfos'])) {
			$this->data['throwableInfos'] = array();
		}
	}
	
	/**
	 * @param \Throwable $e
	 * @return number
	 */
	function check(\Throwable $e) {
		$now = time();
		$cHash = $this->hashThrowable($e);
		$cThrowableInfo = $this->checkLogMailBufferData($now - (60 * 60 * 24), $cHash);
		
		$numThrowables = 0;
		if ($cThrowableInfo === null) {
			$cThrowableInfo = array('times' => 1, 'periodTimes' => 0, 'sent' => $now);
			$numThrowables = 1;
		} else if ($this->sr($cThrowableInfo['times'], $now - $cThrowableInfo['sent'])) {
			$numThrowables = $cThrowableInfo['periodTimes'] + 1;
			$cThrowableInfo['sent'] = $now;
			$cThrowableInfo['times'] += 1;
			$cThrowableInfo['periodTimes'] = 0;
		} else {
			$cThrowableInfo['times'] += 1;
			$cThrowableInfo['periodTimes'] += 1;
		}
		
		$this->data['throwableInfos'][$cHash] = $cThrowableInfo;
		
		@file_put_contents($this->filePath, @json_encode($this->data));
		
		return $numThrowables;
	}
	
	private function checkLogMailBufferData(int $expiryTime, string $cHash) {
		$cThrowableInfo = null;

		foreach ($this->data['throwableInfos'] as $hash => $throwableInfo) {
			if (!isset($throwableInfo['times']) || !is_numeric($throwableInfo['times'])
					|| !isset($throwableInfo['periodTimes']) || !is_numeric($throwableInfo['periodTimes'])
					|| !isset($throwableInfo['sent']) || !is_numeric($throwableInfo['sent'])
					|| $throwableInfo['sent'] < $expiryTime) {
				unset($this->data['throwableInfos'][$hash]);
				continue;
			}
			
			if ($hash == $cHash) {
				$cThrowableInfo = $throwableInfo;
			}
		}
		
		return $cThrowableInfo;
	}
	
	/**
	 * @param int $times
	 * @param int $period
	 * @return boolean
	 */
	private function sr(int $times, int $period) {
		if ($times > 120) {
			$times = 120;
		}
		
		return $period > $times / 2 * 60;
	}
	
	/**
	 * @param \Throwable $t
	 * @return string
	 */
	private function hashThrowable(\Throwable $t) {
		$str = '';
		
		do {
			$str .= get_class($t) . ':' . $t->getFile() . ':' . $t->getLine();
		} while(null !== ($t = $t->getPrevious()));
		
		return md5($str);
	}
}

// class PHPErrorException extends \ErrorException {
// 	private $lateThrown = false;
// 	/**
// 	 * 
// 	 * @param bool $bool
// 	 */
// 	public function setLateThrown($bool) {
// 		$this->lateThrown = $bool;
// 	}
// 	/**
// 	 * @return boolean
// 	 */
// 	public function isLateThrown() {
// 		return $this->lateThrown;
// 	}

// }

class LoggingFailedException extends \RuntimeException {
	
}

// class PHPFatalErrorException extends PHPErrorException {

// }

abstract class ErrorAdapter extends \Error {
	public function __construct(string $message, int $code = null, string $fileFsPath = null,
			int $line = null, \Throwable $previous = null) {
		parent::__construct($message, $code, $previous);

		$this->file = $fileFsPath;
		$this->line = $line;
	}
}

class WarningError extends ErrorAdapter {

}

class NoticeError extends ErrorAdapter {
	
}

class RecoverableError extends ErrorAdapter {

}

class FatalError extends ErrorAdapter {
	
}

class StrictError extends ErrorAdapter {

}

class ParseError extends ErrorAdapter {
	
}

class DeprecatedError extends ErrorAdapter {
	
}

// class PHPParseException extends PHPErrorException {

// }

// class PHPDeprecatedException extends PHPErrorException {
	
// }

// class PHPStrictException extends PHPErrorException {

// }
