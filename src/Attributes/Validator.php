<?php

namespace Yabx\RestBundle\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validator {

	protected string|array $validator;

	public function __construct(string|array $validator) {
		$this->validator = $validator;
	}

	public function getValidator(): string|array {
		return $this->validator;
	}

}
