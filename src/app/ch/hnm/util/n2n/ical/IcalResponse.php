<?php
namespace ch\hnm\util\n2n\ical;

use n2n\web\http\Response;
use n2n\util\type\ArgUtils;
use ch\hnm\util\n2n\ical\impl\IcalProperties;
use n2n\web\http\payload\BufferedPayload;

class IcalResponse extends BufferedPayload {
	const TYPE_CALENDAR = 'VCALENDAR';
	const KEY_VERSION = 'VERSION';
	const KEY_PRODID = 'PRODID';
	
	private $version;
	private $components;
	private $productId;
	private $finalProductId = null;
	
	public function __construct(array $components, string $version = '2.0', string $productId = null)  {
		ArgUtils::valArray($components, IcalComponent::class);
		
		$this->version = $version;
		$this->components = $components;
		$this->productId = $productId;
	}
		
	public function setComponents(array $components) {
		ArgUtils::valArray($components, IcalComponent::class);
		
		$this->components = $components;
	}
	
	public function setVersion(string $version) {
		$this->version = $version;
	}
	
	public function setProductId(string $productId) {
		$this->productId = $productId;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\http\BufferedResponseObject::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		$calenderProperties = new IcalProperties(array(IcalComponent::KEY_BEGIN => self::TYPE_CALENDAR,
				self::KEY_VERSION => $this->version,
				self::KEY_PRODID => $this->finalProductId ?? $this->productId));
		array_unshift($this->components, $calenderProperties);
		array_push($this->components, new IcalProperties(array(IcalComponent::KEY_END => self::TYPE_CALENDAR)));
		
		return implode('', $this->components);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\ResponseObject::prepareForResponse()
	 */
	public function prepareForResponse(Response $response): void {
		if (null === $this->productId) {
			$request = $response->getRequest();
			$this->finalProductId = $request->getHostUrl()->ext($request->getRelativeUrl());
		} else {
			$this->finalProductId = $this->productId;
		}
		$response->setHeader('Content-Type: text/calendar');
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\ResponseObject::toKownResponseString()
	 */
	public function toKownPayloadString(): string {
		return 'Ical Response';
	}
}