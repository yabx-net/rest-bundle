<?php

namespace Yabx\RestBundle\Validator;

use ReflectionClass;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EnumChoiceValidator extends ConstraintValidator {

    public function validate(mixed $value, Constraint $constraint) {

        if(!$constraint instanceof EnumChoice) {
            throw new UnexpectedTypeException($constraint, EnumChoice::class);
        }

        if(!class_exists($constraint->enum)) {
            throw new RuntimeException('Invalid enum: ' . $constraint->enum);
        }

        $rc = new ReflectionClass($constraint->enum);
        if(!$rc->isEnum()) {
            throw new RuntimeException('Not enum: ' . $constraint->enum);
        }

        if(!isset($value)) return;

        if(!is_scalar($value)) $value = $value->value;

        $cases = [];
        foreach($constraint->enum::cases() as $case) {
            $cases[] = $case->value;
        }

        if(!in_array($value, $cases)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ options }}', implode(', ', $cases))
                ->addViolation()
            ;
        }

    }

}
