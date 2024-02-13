<?php

namespace Yabx\RestBundle\Validator;

use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EnumChoiceValidator extends ConstraintValidator {

    public function validate(mixed $value, Constraint $constraint): void {

        if(!$constraint instanceof EnumChoice) {
            throw new UnexpectedTypeException($constraint, EnumChoice::class);
        }

        if(!class_exists($constraint->enum) || !method_exists($constraint->enum, 'cases')) {
            throw new RuntimeException('Invalid enum: ' . $constraint->enum);
        }

        if(!isset($value)) return;
        $cases = array_map(fn($item) => $item->value, call_user_func([$constraint->enum, 'cases']));

        if(is_array($value)) {
            foreach ($value as $item) {
                $this->validateItem($item, $constraint, $cases);
            }
        } else {
            $this->validateItem($value, $constraint, $cases);
        }

    }

    private function validateItem(mixed $value, EnumChoice $constraint, array $cases): void {
        if(!is_scalar($value)) $value = $value->value;
        if(!in_array($value, $cases)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ options }}', implode(', ', $cases))
                ->addViolation()
            ;
        }
    }

}
