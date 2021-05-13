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
namespace n2n\core\config\build;

use n2n\util\StringUtils;
use n2n\web\http\controller\ControllerDef;
use n2n\mail\smtp\SmtpConfig;
use n2n\l10n\DateTimeFormat;
use n2n\l10n\N2nLocale;
use n2n\web\http\Subsystem;
use n2n\persistence\PersistenceUnitConfig;
use n2n\util\crypt\EncryptionDescriptor;
use n2n\io\fs\FsPath;
use n2n\util\uri\Url;
use n2n\core\config\AppConfig;
use n2n\core\config\GeneralConfig;
use n2n\core\config\WebConfig;
use n2n\core\config\MailConfig;
use n2n\core\config\IoConfig;
use n2n\core\config\FilesConfig;
use n2n\core\config\ErrorConfig;
use n2n\core\config\DbConfig;
use n2n\core\config\OrmConfig;
use n2n\core\config\N2nLocaleConfig;
use n2n\l10n\L10nStyle;
use n2n\l10n\L10nConfig;
use n2n\l10n\PseudoL10nConfig;
use n2n\l10n\L10nFormat;
use n2n\web\http\Supersystem;

class AppConfigFactory {
	const GROUP_GENERAL = 'general';
	const GROUP_WEB = 'web';
	const GROUP_ROUTING = 'routing';
	const GROUP_MAIL = 'mail';
	const GROUP_IO = 'io';
	const GROUP_FILES = 'files';
	const GROUP_ERROR = 'error';
	const GROUP_DATABASE = 'database';
	const GROUP_ORM = 'orm';
	const GROUP_LOCALES = 'locales';
	const GROUP_L10N = 'l10n';
	const GROUP_PSEUDO_L10N = 'pseudo_l10n';
	
	private $publicFsPath;
	
	public function __construct(FsPath $publicFsPath) {
		$this->publicFsPath = $publicFsPath;
	}
	
	public function getGroupNames() {
		return array(self::GROUP_GENERAL, self::GROUP_WEB, self::GROUP_ROUTING, self::GROUP_MAIL, self::GROUP_IO, 
				self::GROUP_ERROR, self::GROUP_DATABASE, self::GROUP_ORM, self::GROUP_LOCALES, self::GROUP_L10N, 
				self::GROUP_PSEUDO_L10N);
	}
	
	/**
	 * @param CombinedConfigSource $combinedConfigSource
	 * @param string $stage
	 * @return \n2n\core\config\AppConfig
	 */
	public function create(CombinedConfigSource $combinedConfigSource, $stage, $stageExplizit = false) {
		$reader = new GroupedConfigSourceReader($combinedConfigSource);
		$reader->initialize($stage, $stageExplizit, self::getGroupNames(), array(self::GROUP_ROUTING));

		return new AppConfig(
				$this->createGeneralConfig($reader->getGroupReaderByGroupName(self::GROUP_GENERAL)),
				$this->createWebConfig($reader->getGroupReaderByGroupName(self::GROUP_WEB),
						$reader->getGroupReaderByGroupName(self::GROUP_ROUTING),
						$reader->getExtendedGroupReadersByGroupName(self::GROUP_ROUTING)),
				$this->createMailConfig($reader->getGroupReaderByGroupName(self::GROUP_MAIL)),
				$this->createIoConfig($reader->getGroupReaderByGroupName(self::GROUP_IO)),
				$this->createFilesConfig($reader->getGroupReaderByGroupName(self::GROUP_FILES)),
				$this->createErrorConfig($reader->getGroupReaderByGroupName(self::GROUP_ERROR)),
				$this->createDatabaseConfig($reader->getGroupReaderByGroupName(self::GROUP_DATABASE)),
				$this->createOrmConfig($reader->getGroupReaderByGroupName(self::GROUP_ORM)),
				$this->createN2nLocalesConfig($reader->getGroupReaderByGroupName(self::GROUP_LOCALES)),
				$this->createL10nConfig($reader->getGroupReaderByGroupName(self::GROUP_L10N)),
				$this->createPseudoL10nConfig($reader->getGroupReaderByGroupName(self::GROUP_PSEUDO_L10N)));
	}
	
	const PAGE_NAME_KEY = 'page.name';
	const PAGE_NAME_DEFAULT = 'New awesome project';
	const PAGE_URL_KEY = 'page.url';
	
	const APPLICATION_NAME_KEY = 'application.name';
	const APPLICATION_NAME_DEFAULT = 'newAwesomeProject';
	const APPLICATION_LOG_LEVEL_KEY = 'application.log_level';
	const APPLICATION_BATCH_CONTROLLER_NAMES_KEY = 'application.batch_jobs';
	
	private function createGeneralConfig(GroupReader $groupReader) {
		return new GeneralConfig(
				$groupReader->getString(self::PAGE_NAME_KEY, false, self::PAGE_NAME_DEFAULT),
				$groupReader->getString(self::PAGE_URL_KEY, false, null),
				$groupReader->getNoIoStrictSpecialCharsString(self::APPLICATION_NAME_KEY, false, self::APPLICATION_NAME_DEFAULT),
				$groupReader->getString(self::APPLICATION_LOG_LEVEL_KEY, false),
				array_filter($groupReader->getScalarArray(self::APPLICATION_BATCH_CONTROLLER_NAMES_KEY, false, array())));
	}
	
	const RESPONSE_CACHING_ENABLED_KEY = 'response.caching_enabled';
	const RESPONSE_CACHING_ENABLED_DEFAULT = true;
	const RESPONSE_BROWSER_CACHING_ENABLED_KEY = 'response.browser_caching_enabled';
	const RESPONSE_BROWSER_CACHING_ENABLED_DEFAULT = true;
	const RESPONSE_SEND_ETAG_ALLOWED_KEY = 'response.send_etag';
	const RESPONSE_SEND_ETAG_ALLOWED_DEFAULT = true;
	const RESPONSE_SEND_LAST_MODIFIED_ALLOWED_KEY = 'response.send_last_modified';
	const RESPONSE_SEND_LAST_MODIFIED_ALLOWED_DEFAULT = true;
	const RESPONSE_SERVER_PUSH_ALLOWED_KEY = 'response.server_push';
	const RESPONSE_SERVER_PUSH_ALLOWED_DEFAULT = true;
	
	const VIEW_CACHING_ENABLED_KEY = 'view.caching_enabled';
	const VIEW_CACHING_ENABLED_DEFAULT = true;
	
	const VIEW_TYPE_KEY_PREFIX = 'view.type.';
	
	const CONTROLLERS_KEY = 'controllers';
	
	const FILTERS_KEY = 'filters';
	
	const PRECACHE_FILTERS_KEY = 'precache_filters';
	
	const HOST_KEY = 'host';
	const CONTEXT_PATH_KEY = 'context_path';
	const LOCALES_KEY = 'locales';
	
	const DISPATCH_PROPERTY_FACTORIES_NAMES_KEY = 'dispatch.property_providers';
	const DISPATCH_TARGET_CRYPT_ENABLED_KEY = 'dispatch.target_crypt_enabled';
	const DISPATCH_TARGET_CRYPT_ENABLED_DEFAULT = true;
	const DISPATCH_TARGET_CRYPT_ALGORITHM_KEY = 'dispatch.target_crypt_algorithm';
	const DISPATCH_TARGET_CRYPT_ALGORITHM_DEFAULT = EncryptionDescriptor::ALGORITHM_AES_256_CTR;
	
	const LOCALE_ALIASES_KEY = 'locale_aliases';

	private function createWebConfig(GroupReader $groupReader, GroupReader $supersystemGroupReader, array $subsystemGroupReaders) {
		return new WebConfig(
				$groupReader->getBool(self::RESPONSE_CACHING_ENABLED_KEY, false, 
						self::RESPONSE_CACHING_ENABLED_DEFAULT),
				$groupReader->getBool(self::RESPONSE_BROWSER_CACHING_ENABLED_KEY, false, 
						self::RESPONSE_BROWSER_CACHING_ENABLED_DEFAULT),
				$groupReader->getBool(self::RESPONSE_SEND_ETAG_ALLOWED_KEY, false, 
						self::RESPONSE_SEND_ETAG_ALLOWED_DEFAULT),
				$groupReader->getBool(self::RESPONSE_SEND_LAST_MODIFIED_ALLOWED_KEY, false,
						self::RESPONSE_SEND_LAST_MODIFIED_ALLOWED_DEFAULT),
				$groupReader->getBool(self::RESPONSE_SERVER_PUSH_ALLOWED_KEY, false,
						self::RESPONSE_SERVER_PUSH_ALLOWED_DEFAULT),
				$groupReader->getBool(self::VIEW_CACHING_ENABLED_KEY, false, self::VIEW_CACHING_ENABLED_DEFAULT),
				self::extractStringPropertyArray($groupReader, self::VIEW_TYPE_KEY_PREFIX),
				$this->createControllerDefs($supersystemGroupReader, $subsystemGroupReaders, self::CONTROLLERS_KEY),
				$this->createControllerDefs($supersystemGroupReader, $subsystemGroupReaders, self::FILTERS_KEY),
				$this->createControllerDefs($supersystemGroupReader, $subsystemGroupReaders, self::PRECACHE_FILTERS_KEY),
				$this->createSupersystem($supersystemGroupReader),
				$this->createSubsystemConfigs($subsystemGroupReaders),
				array_unique($groupReader->getScalarArray(self::DISPATCH_PROPERTY_FACTORIES_NAMES_KEY, false, array())),
				($groupReader->getBool(self::DISPATCH_TARGET_CRYPT_ENABLED_KEY, false, self::DISPATCH_TARGET_CRYPT_ENABLED_DEFAULT) 
						? $groupReader->getString(self::DISPATCH_TARGET_CRYPT_ALGORITHM_KEY, false, self::DISPATCH_TARGET_CRYPT_ALGORITHM_DEFAULT) : null),
				$groupReader->getN2nLocaleKeyArray(self::LOCALE_ALIASES_KEY));
	}
	
	const CONTR_SEPARATOR = '>';
	
	private function createControllerDefs(GroupReader $groupReader, array $subsystemGroupReaders, string $attributeName) {
		$controllerDefs = array();
		
		foreach ((array) $groupReader->getScalarArray($attributeName) as $contextPath => $controllerClassName) {
			if (!strlen($controllerClassName)) continue;
			
			$controllerDefs[] =  $this->createControllerDef($controllerClassName, null, $contextPath);
		}
		
		foreach ($subsystemGroupReaders as $subsystemName => $subsystemGroupReader) {
			foreach ($subsystemGroupReader->getScalarArray($attributeName) as $contextPath => $controllerClassName) {
				if (!strlen($controllerClassName)) continue;
				
				$controllerDefs[] =  $this->createControllerDef($controllerClassName, $subsystemName, $contextPath);
			}
		}
		
		return $controllerDefs;
	}
	
	private function createControllerDef(string $controllerClassName, string $subsystemName = null, string $contextPath) {
		$parts = explode(self::CONTR_SEPARATOR, $controllerClassName, 2);
		if (count($parts) > 1) {
			$contextPath = trim($parts[0]);
			$controllerClassName = trim($parts[1]);
		}
		return new ControllerDef($controllerClassName, $subsystemName, $contextPath);
	}
	
	private function createSupersystem(GroupReader $groupReader) {
		return new Supersystem($groupReader->getN2nLocaleArray(self::LOCALES_KEY));
	}
	
	private function createSubsystemConfigs(array $subsystemGroupReaders): array {
		$subsystemConfigs = array();
		foreach ($subsystemGroupReaders as $subsystemName => $subsystemGroupReader) {
			$subsystemConfigs[$subsystemName] = new Subsystem($subsystemName, 
					$subsystemGroupReader->getString(self::HOST_KEY, false), 
					$subsystemGroupReader->getString(self::CONTEXT_PATH_KEY, false),
					$subsystemGroupReader->getN2nLocaleArray(self::LOCALES_KEY));
		}
		return $subsystemConfigs;
	}

	const MAIL_SENDING_ENABLED_KEY = 'mail_sending_enabled';
	const MAIL_SENDING_ENABLED_DEFAULT = true;
	const DEFAULT_ADDRESSER_KEY = 'default_addresser';
	const ADDRESS_SYSTEM_MANAGER_KEY = 'address.system_manager';
	const ADDRESS_CUSTOMER_ADDRESS_KEY = 'address.customer';
	const ADDRESS_NOTIFICATION_RECIPIENTS_KEY = 'address.notification_recipients';
	const SMTP_HOST_KEY = 'smtp.host';
	const SMTP_PORT_KEY = 'smtp.port';
	const SMTP_SECURITY_MODE = 'smtp.security_mode';
	const SMTP_AUTHENTICATION_REQUIRED_KEY = 'smtp.authentification.required';
	const SMTP_AUTHENTICATION_REQUIRED_DEFAULT = false;
	const SMTP_AUTHENTICATION_USER = 'smtp.authentification.user';
	const SMTP_AUTHENTICATION_PASSWORD_KEY = 'smtp.authentification.password';
	
	private function createMailConfig(GroupReader $groupReader) {
		// @todo redo
		return new MailConfig(
				$groupReader->getBool(self::MAIL_SENDING_ENABLED_KEY, false, 
						self::MAIL_SENDING_ENABLED_DEFAULT),
				$groupReader->getString(self::DEFAULT_ADDRESSER_KEY, false),
				$groupReader->getString(self::ADDRESS_SYSTEM_MANAGER_KEY, false),
				$groupReader->getString(self::ADDRESS_CUSTOMER_ADDRESS_KEY, false),
				$groupReader->getScalarArray(self::ADDRESS_NOTIFICATION_RECIPIENTS_KEY, false, array()),
				new SmtpConfig(
						$groupReader->getString(self::SMTP_HOST_KEY, false),
						$groupReader->getString(self::SMTP_AUTHENTICATION_USER, false),
						$groupReader->getString(self::SMTP_AUTHENTICATION_PASSWORD_KEY, false),
						$groupReader->getInt(self::SMTP_PORT_KEY, false),
						$groupReader->getBool(self::SMTP_AUTHENTICATION_REQUIRED_KEY, false, self::SMTP_AUTHENTICATION_REQUIRED_DEFAULT),
						$groupReader->getEnum(self::SMTP_SECURITY_MODE, 
								array(SmtpConfig::SECURITY_MODE_SSL, SmtpConfig::SECURITY_MODE_TLS), false)));
	}

	const PUBLIC_DIR_PERMISSION_KEY = 'public.dir_permission';
	const PUBLIC_DIR_PERMISSION_DEFAULT = '0700';
	const PUBLIC_FILE_PERMISSION_KEY = 'public.file_permission';
	const PUBLIC_FILE_PERMISSION_DEFAULT = '0600';
	const PRIVATE_DIR_PERMISSION_KEY = 'private.dir_permission';
	const PRIVATE_DIR_PERMISSION_DEFAULT = '0700';
	const PRIVATE_FILE_PERMISSION_KEY = 'private.file_permission';
	const PRIVATE_FILE_PERMISSION_DEFAULT = '0600';
	
	
	private function createIoConfig(GroupReader $groupReader) {
		return new IoConfig(
				$groupReader->getString(self::PUBLIC_DIR_PERMISSION_KEY, false, self::PUBLIC_DIR_PERMISSION_DEFAULT),
				$groupReader->getString(self::PUBLIC_FILE_PERMISSION_KEY, false, self::PUBLIC_FILE_PERMISSION_DEFAULT),
				$groupReader->getString(self::PRIVATE_DIR_PERMISSION_KEY, false, self::PRIVATE_DIR_PERMISSION_DEFAULT),				
				$groupReader->getString(self::PRIVATE_FILE_PERMISSION_KEY, false, self::PRIVATE_FILE_PERMISSION_DEFAULT));
	}

	const ASSETS_DIR_KEY = 'assets.dir';
	const ASSETS_DIR_DEFAULT = 'assets';
	const ASSETS_URL_KEY = 'assets.url';
	const MANAGER_PUBLIC_DIR_KEY = 'manager.public.dir';
	const MANAGER_PUBLIC_DIR_DEFAULT = 'files';
	const MANAGER_PUBLIC_URL_KEY = 'manager.public.url';
	const MANAGER_PUBLIC_URL_DEFAULT = 'files';
	const MANAGER_PRIVATE_DIR_KEY = 'manager.private.dir';
	
	private function createFilesConfig(GroupReader $groupReader) {
		return new FilesConfig(
				$this->parsePublicFsPath($groupReader->getString(self::ASSETS_DIR_KEY,
						false, self::ASSETS_DIR_DEFAULT)),
				Url::create($groupReader->getString(self::ASSETS_URL_KEY, false, self::ASSETS_DIR_DEFAULT)),
				$this->parsePublicFsPath($groupReader->getString(self::MANAGER_PUBLIC_DIR_KEY,
						false, self::MANAGER_PUBLIC_DIR_DEFAULT)),
				Url::create($groupReader->getString(self::MANAGER_PUBLIC_URL_KEY,
						false, self::MANAGER_PUBLIC_URL_DEFAULT)),
				$groupReader->getString(self::MANAGER_PRIVATE_DIR_KEY, false, null));
	}
	
	private function parsePublicFsPath($name) {
		$fsPath = new FsPath($name);
		if ($fsPath->isAbsolute()) return $fsPath;
		
		return $this->publicFsPath->ext($fsPath);
	}
	
	const STRICT_ATTITUDE_KEY = 'strict_attitude';
	const STRICT_ATTITUDE_DEFAULT = true;
	const STARTUP_DETECT_ERRORS_KEY = 'startup.ignore_errors';
	const STARTUP_DETECT_ERRORS_DEFAULT = true;
	const STARTUP_DETECT_BAD_REQUESTS_KEY = 'startup.detect_bad_requests';
	const STARTUP_DETECT_BAD_REQUESTS_DEFAULT = true;
	const LOG_SAVE_DETAIL_INFO_KEY = 'log.save_detail_info';
	const LOG_SAVE_DETAIL_INFO_DEFAULT = true;
	const LOG_SEND_MAIL_KEY = 'log.send_mail';
	const LOG_SEND_MAIL_DEFAULT = false;
	const LOG_MAIL_RECIPIENT_KEY = 'log.mail_recipient';
	const LOG_HANDLE_HTTP_STATUS_EXCEPTIONS_KEY = 'log.handle_http_status_exceptions';
	const LOG_HANDLE_HTTP_STATUS_EXCEPTIONS_DEFAULT = false;
	const LOG_EXCLUDED_HTTP_STATUS_KEY = 'log.excluded_http_status_exceptions';
	const ERROR_VIEW_KEY_PREFIX = 'error_view.';
	
	
	private function createErrorConfig(GroupReader $groupReader) {
		return new ErrorConfig(
				$groupReader->getBool(self::STRICT_ATTITUDE_KEY, false, self::STRICT_ATTITUDE_DEFAULT),
				$groupReader->getBool(self::STARTUP_DETECT_ERRORS_KEY, false, self::STARTUP_DETECT_ERRORS_DEFAULT),
				$groupReader->getBool(self::STARTUP_DETECT_BAD_REQUESTS_KEY, false, self::STARTUP_DETECT_BAD_REQUESTS_DEFAULT),
				$groupReader->getBool(self::LOG_SAVE_DETAIL_INFO_KEY, false, self::LOG_SAVE_DETAIL_INFO_DEFAULT),
				$groupReader->getBool(self::LOG_SEND_MAIL_KEY, false, self::LOG_SEND_MAIL_DEFAULT),
				$groupReader->getString(self::LOG_MAIL_RECIPIENT_KEY, false),
				$groupReader->getString(self::LOG_HANDLE_HTTP_STATUS_EXCEPTIONS_KEY, false, self::LOG_HANDLE_HTTP_STATUS_EXCEPTIONS_DEFAULT),
				$groupReader->getScalarArray(self::LOG_EXCLUDED_HTTP_STATUS_KEY, false, array()),
				self::extractStringPropertyArray($groupReader, self::ERROR_VIEW_KEY_PREFIX));
	}
		
	const KEY_EXT_DSN_URI = '.dsn_uri';
	const KEY_EXT_USER = '.user';
	const KEY_EXT_PASSWORD = '.password';
	const KEY_EXT_TRANSACTION_ISOLATION_LEVEL = '.transaction_isolation_level';
	const KEY_EXT_DIALECT_CLASS = '.dialect';
	
	private function createDatabaseConfig(GroupReader $groupReader) {
		$persistenceUnitConfigs = array();
		foreach (self::extractPathParts($groupReader) as $name) {
			$persistenceUnitConfigs[$name] = new PersistenceUnitConfig($name,
					$groupReader->getString($name . self::KEY_EXT_DSN_URI, true),
					$groupReader->getString($name . self::KEY_EXT_USER, true),
					$groupReader->getString($name . self::KEY_EXT_PASSWORD, false),
					$groupReader->getString($name . self::KEY_EXT_TRANSACTION_ISOLATION_LEVEL, false,
							PersistenceUnitConfig::TIL_SERIALIZABLE),
					$groupReader->getString($name . self::KEY_EXT_DIALECT_CLASS, true)); 
		}
				
// 		$persistenceUnitConfigs[] = new PersistenceUnitConfig('default', 
// 				'mysql:host=127.0.0.1;dbname=hangar', 'root', '', 'SERIALIZABLE', 
// 				new \ReflectionClass('n2n\impl\persistence\meta\mysql\MysqlDialect'));
		return new DbConfig($persistenceUnitConfigs);
	}

	const ENTITIES_KEY = 'entities';
	const ENTITY_PROPERTY_PROVIDERS_KEY = 'entity_property_providers';
	const ENTITY_NAMING_STRATEGY_KEY = 'naming_strategy';
	
	private function createOrmConfig(GroupReader $groupReader) {
		return new OrmConfig(array_unique($groupReader->getScalarArray(self::ENTITIES_KEY, false, array())),
				array_unique($groupReader->getScalarArray(self::ENTITY_PROPERTY_PROVIDERS_KEY, false, array())),
				$groupReader->getString(self::ENTITY_NAMING_STRATEGY_KEY, false));
	}

	const FALLBACK_LOCALE_ID_KEY = 'fallback';
	const FALLBACK_LOCALE_ID_DEFAULT = 'en';
	const ADMIN_LOCALE_ID_KEY = 'admin';
	const ADMIN_LOCALE_ID_DEFAULT = 'rm_CH';
	const DEFAULT_LOCALE_ID_KEY = 'default';
	const DEFAULT_LOCALE_ID_DEFAULT = 'rm_CH';
	
	private function createN2nLocalesConfig(GroupReader $groupReader) {
		return new N2nLocaleConfig(
				$groupReader->getN2nLocale(self::FALLBACK_LOCALE_ID_KEY, false, new N2nLocale(self::FALLBACK_LOCALE_ID_DEFAULT)),
				$groupReader->getN2nLocale(self::ADMIN_LOCALE_ID_KEY, false, new N2nLocale(self::ADMIN_LOCALE_ID_DEFAULT)),
				$groupReader->getN2nLocale(self::DEFAULT_LOCALE_ID_KEY, false, new N2nLocale(self::DEFAULT_LOCALE_ID_DEFAULT)));
	}
	
	const L10N_ENABLED_KEY = 'l10n_enabled';
	const L10N_ENABLED_DEFAULT = true;
	
	const PROP_DATE_DEFAULT_SUFFIX = '.date.default';
	const PROP_DATE_INPUT_SUFFIX = '.date.input';
	
	const PROP_TIME_DEFAULT_SUFFIX = '.time.default';
	const PROP_TIME_INPUT_SUFFIX = '.time.input';
	
	const PROP_DATETIME_FORMAT_SUFFIX = '.datetime.format';
	
	private function createL10nConfig(GroupReader $groupReader) {
		return new L10nConfig($groupReader->getBool(self::L10N_ENABLED_KEY, false, self::L10N_ENABLED_DEFAULT),
				$this->createL10nStyles($groupReader));	
	}
	
	private function createL10nStyles(GroupReader $groupReader) {
		$l10nStyles = array();
		foreach (self::extractPathParts($groupReader) as $n2nLocaleId) {
			$l10nStyles[$n2nLocaleId] = new L10nStyle(
					$groupReader->getString($n2nLocaleId . self::PROP_DATE_DEFAULT_SUFFIX, false),
					$groupReader->getString($n2nLocaleId . self::PROP_TIME_DEFAULT_SUFFIX, false),
					$groupReader->getString($n2nLocaleId . self::PROP_DATE_INPUT_SUFFIX, false),
					$groupReader->getString($n2nLocaleId . self::PROP_TIME_INPUT_SUFFIX, false),
					$groupReader->getString($n2nLocaleId . self::PROP_DATETIME_FORMAT_SUFFIX, false));
		}
		return $l10nStyles;
	}

	const PROP_DATE_PATTERN_INPUT_SUFFIX = '.date.pattern.input';
	const PROP_TIME_PATTERN_INPUT_SUFFIX = '.time.pattern.input';
	
	const PROP_DATE_PATTERN_STYLE_SUFFIX = '.date.pattern.';
	const PROP_TIME_PATTERN_STYLE_SUFFIX = '.time.pattern.';
	
	private function createPseudoL10nConfig(GroupReader $groupReader) {
		return new PseudoL10nConfig($this->createL10nStyles($groupReader), $this->createL10nFormats($groupReader));	
	}
	
	private function createL10nFormats(GroupReader $groupReader) {
		$l10nFormats = array();
		foreach (self::extractPathParts($groupReader) as $n2nLocaleId) {
			$l10nFormats[$n2nLocaleId] = new L10nFormat(
					self::extractPatternArray($groupReader, $n2nLocaleId . self::PROP_DATE_PATTERN_STYLE_SUFFIX),
					self::extractPatternArray($groupReader, $n2nLocaleId . self::PROP_TIME_PATTERN_STYLE_SUFFIX),
					$groupReader->getString(self::PROP_DATE_PATTERN_INPUT_SUFFIX, false),
					$groupReader->getString(self::PROP_DATE_PATTERN_INPUT_SUFFIX, false));
		}
		return $l10nFormats;
	}
	
	private function extractPatternArray(GroupReader $groupReader, string $prefix) {
		return array(
				DateTimeFormat::STYLE_SHORT => $groupReader->getString(
						$prefix . DateTimeFormat::STYLE_SHORT, false),
				DateTimeFormat::STYLE_MEDIUM => $groupReader->getString(
						$prefix . DateTimeFormat::STYLE_MEDIUM, false),
				DateTimeFormat::STYLE_LONG => $groupReader->getString(
						$prefix . DateTimeFormat::STYLE_LONG, false),
				DateTimeFormat::STYLE_FULL => $groupReader->getString(
						$prefix . DateTimeFormat::STYLE_FULL, false));
	}
	
	
	const DEFAULT_LEVEL_SEPARATOR = '.';
	
	private function extractPathParts(GroupReader $groupReader, string $prefix = null) {
		$names = array();
		foreach ($groupReader->getNames() as $key) {
			if ($prefix !== null) {
				if (!StringUtils::startsWith($prefix, $key)) continue;
					
				$key = ltrim(mb_substr($key, mb_strlen($prefix)), self::DEFAULT_LEVEL_SEPARATOR);
			}
	
			$keyParts = explode(self::DEFAULT_LEVEL_SEPARATOR, $key, 2);
			$names[] = $keyParts[0];
		}
		return $names;
	}
	
	private function extractStringPropertyArray(GroupReader $groupReader, string $prefix): array {
		$arr = array();
		foreach (self::extractPathParts($groupReader, $prefix) as $propertyName) {
			$arr[$propertyName] = $groupReader->getString($prefix . $propertyName, true);
		}
		return $arr;
	}
	
}
