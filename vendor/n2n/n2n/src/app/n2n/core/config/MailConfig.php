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

use n2n\mail\smtp\SmtpConfig;

class MailConfig {
	
	private $sendingMailEnabled;
	private $defaultAddresser;
	private $systemManagerAddress;
	private $customerAddress;
	private $notificationRecipientAddresses;
	private $defaultSmtpConfig;
	
	public function __construct($sendingMailEnabled, $defaultAddresser, $systemManagerAddress, $customerAddress, 
			array $notificationRecipientAddresses, SmtpConfig $defaultSmtpConfig) {
		$this->sendingMailEnabled = (boolean) $sendingMailEnabled;
		$this->defaultAddresser = (string) $defaultAddresser;
		$this->systemManagerAddress = (string) $systemManagerAddress;
		$this->customerAddress = (string) $customerAddress; 
		$this->notificationRecipientAddresses = $notificationRecipientAddresses;
		$this->defaultSmtpConfig = $defaultSmtpConfig;
	}
	
	public function isSendingMailEnabled() {
		return $this->sendingMailEnabled;
	}
	
	public function getDefaultAddresser() {
		return $this->defaultAddresser;
	}
	
	public function getSystemManagerAddress() {
		return $this->systemManagerAddress;
	}
	
	public function getCustomerAddress() {
		return $this->customerAddress;
	}
	
	public function getNotificationRecipientsAddresses() {
		return $this->notificationRecipientAddresses;
	}
	/**
	 * @return \n2n\mail\smtp\SmtpConfig
	 */
	public function getDefaultSmtpConfig() {
		return $this->defaultSmtpConfig;
	}
}
