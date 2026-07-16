<?php

namespace Yabx\RestBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class EnumChoice extends Constraint {

    public function __construct(
        public string $enum,
        public string $message = 'Invalid value: {{ value }}. Allowed: {{ options }}',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function validatedBy(): string {
        return EnumChoiceValidator::class;
    }

}
