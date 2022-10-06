<?php

namespace Yabx\RestBundle\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL)]
class Name {

	public string $value;

	public function __construct(string $value) {
		$this->value = $value;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function __toString(): string {
		return $this->value;
	}

}
