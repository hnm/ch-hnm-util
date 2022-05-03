<?php
namespace ch\hnm\util\n2n\ical\impl;

use ch\hnm\util\n2n\ical\IcalComponent;

class IcalEvent extends IcalComponent {
	
	const TYPE = 'VEVENT';
	
	const KEY_SUMMARY = 'SUMMARY';
	const KEY_DESCRIPTION = 'DESCRIPTION';
	const KEY_LOCATION = 'LOCATION';
	const KEY_UID = 'UID';
	const KEY_DTSTAMP = 'DTSTAMP';
	const KEY_DTSTART = 'DTSTART';
	const KEY_DTEND = 'DTEND';
	const KEY_URL = 'URL';
	
	private $uid;
	private $dateStart;
	private $dateEnd;
	private $summary;
	private $description;
	private $location;
	private $url;
	private $includeTimezone = false;
	
	private $additionalProperties = array();
	
	public function __construct($uid, \DateTime $dateStart, \DateTime $dateEnd = null) {
		$this->uid = $uid;
		$this->dateStart = $dateStart;
		
		if (null !== $dateEnd) {
			$this->dateEnd = $dateEnd;
		} else {
			$this->dateEnd = $dateStart;
		}
	}

	public function getUid() {
		return $this->uid;
	}

	public function setUid($uid) {
		$this->uid = $uid;
		return $this;
	}

	public function getDateStart() {
		return $this->dateStart;
	}

	public function setDateStart(\DateTime $dateStart) {
		$this->dateStart = $dateStart;
		return $this;
	}

	public function getDateEnd() {
		return $this->dateEnd;
	}

	public function setDateEnd(\DateTime $dateEnd) {
		$this->dateEnd = $dateEnd;
		return $this;
	}

	public function getSummary() {
		return $this->summary;
	}

	public function setSummary($summary) {
		$this->summary = $summary;
		return $this;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	public function getLocation() {
		return $this->location;
	}

	public function setLocation($location) {
		$this->location = $location;
		return $this;
	}
	
	public function setIncludeTimeZone(bool $includeTimeZone) {
		$this->includeTimezone = $includeTimeZone;
		return $this;
	}
	
	public function isIncludeTimeZone() {
		return $this->includeTimezone;
	}

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}
	
	public function addProperty($key, $value) {
		$this->additionalProperties[$key] = $value;
	}

	public function getProperties() {
		$properties = array(self::KEY_BEGIN => self::TYPE);
		
		if (null !== $this->summary) {
			$properties[self::KEY_SUMMARY] = $this->summary;
		}
		
		if (null !== $this->description) {
			$properties[self::KEY_DESCRIPTION] = $this->description;
		}
		
		if (null !== $this->location) {
			$properties[self::KEY_LOCATION] = $this->location;
		}
		
		$properties[self::KEY_UID] = $this->uid;
		
		if (null !== $this->url) {
			$properties[self::KEY_URL] = $this->url;
		}
		
		$properties[self::KEY_DTSTART] = $this->buildDateTimeValue($this->dateStart);
		$properties[self::KEY_DTEND] =  $this->buildDateTimeValue($this->dateEnd);
		$properties[self::KEY_DTSTAMP] = $this->buildDateTimeValue(new \DateTime());
		
		$properties[self::KEY_END] = self::TYPE;
		
		return $properties;
	}
	
	private function buildDateTimeValue(\DateTime $dateTime) {
		if ($this->includeTimezone) {
			$utcDateTime = clone $dateTime;
			$utcDateTime->setTimezone(new \DateTimeZone('UTC'));
			
			return $utcDateTime->format("Ymd\THis\Z");
		}
		
		return $dateTime->format('Ymd\THis');
	}
	
	public static function create($uid, \DateTime $dateStart, \DateTime $dateEnd = null) {
		return new IcalEvent($uid, $dateStart, $dateEnd);
	}
}
