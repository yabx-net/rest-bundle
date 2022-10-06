<?php

namespace Yabx\RestBundle\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Processor {

	protected string|array $processor;

	public function __construct(string|array $processor) {
		$this->processor = $processor;
	}

	public function getProcessor(): string|array {
		return $this->processor;
	}

}
