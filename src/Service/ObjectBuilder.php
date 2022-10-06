<?php

namespace Yabx\RestBundle\Service;

use DateTime;
use Exception;
use Throwable;
use ReflectionClass;
use RuntimeException;
use DateTimeInterface;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionException;
use Yabx\RestBundle\Attributes\Name;
use Symfony\Component\Validator\Constraint;
use Yabx\RestBundle\Attributes\Validator;
use Yabx\RestBundle\Attributes\Processor;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ObjectBuilder {

	protected ValidatorInterface $validator;
	protected ?TranslatorInterface $translator;

	public function __construct(ValidatorInterface $validator, ?TranslatorInterface $translator) {
		$this->validator = $validator;
		$this->translator = $translator;
	}

	/**
	 * @template T
	 * @param class-string $className
	 * @param array $data
	 * @param callable|null $resolve
	 * @return T
	 * @throws ReflectionException
	 */
	public function build(string $className, array $data = [], callable $resolve = null): object {
		$rc = new ReflectionClass($className);
		$object = $rc->newInstanceWithoutConstructor();
		$defaults = $rc->getDefaultProperties();
		foreach($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $rp) {
			/** @var ReflectionNamedType|ReflectionUnionType|null $type */
			$type = $rp->getType();

            if($type instanceof ReflectionUnionType) {
                $type = $type->getTypes()[0];
            }

			$key = $rp->getName();
			$isSet = true;
			if(key_exists($key, $data)) {
				$value = $data[$key];
			} elseif(key_exists($key, $defaults)) {
				$value = $defaults[$key];
			} else {
				$value = null;
				$isSet = false;
			}
			if($type->getName() !== gettype($value)) {
				if($type->isBuiltin()) {
					$value = $this->cast($value, $type->getName());
				} elseif(is_array($value)) {
					$value = $this->build($type->getName(), $value);
				} elseif(class_exists($type->getName()) && $value !== null) {
                    $rc1 = new ReflectionClass($type->getName());
                    if($rc1->isEnum()) {
                        $value = is_scalar($value) ? $type->getName()::from($value) : $value;
                    }
                }
			}

			if($name = $rp->getAttributes(Name::class)) {
				$name = $name[0]->newInstance();
			} else {
				$name = $key;
			}

			$name = $this->translator?->trans($name) ?? $name;

			if($processors = $rp->getAttributes(Processor::class)) {
				foreach($processors as $pa) {
					/** @var Processor $pi */
					$pi = $pa->newInstance();
					try {
						$processor = $pi->getProcessor();
						if(is_string($processor)) $processor = [$object, $processor];
						$value = call_user_func($processor, $value);
					} catch(Throwable $err) {
						throw new Exception($name . ': ' . $err->getMessage());
					}
				}
			}

			if($validators = $rp->getAttributes(Validator::class)) {
				foreach($validators as $va) {
					/** @var Validator $vi */
					$vi = $va->newInstance();
					try {
						$validator = $vi->getValidator();
						if(is_string($validator)) $validator = [$object, $validator];
						call_user_func($validator, $value);
					} catch(Throwable $err) {
						throw new Exception($name . ': ' . $err->getMessage());
					}
				}
			}

			$asserts = [];

			// Processing Attributes
			foreach($rp->getAttributes() as $a) {
				$a = $a->newInstance();
				if($a instanceof Constraint) {
					$asserts[] = $a;
				}
			}

			$err = $this->validator->validate($value, $asserts);
			if($err->count()) {
				$err = $err->get(0);
				throw new Exception($name . ': ' . $err->getMessage());
			}

			if($resolve) {
				$value = call_user_func($resolve, $key, $value);
			}

			if($isSet) $object->$key = $value;
		}
		return $object;
	}

	protected function cast($value, string $type): float|DateTime|null|int|bool|array|string {
		if($value === null) {
			return null;
		} elseif($type === 'bool' || $type === 'boolean') {
			return (bool)$value;
		} elseif($type === 'int' || $type === 'integer') {
			return (int)$value;
		} elseif($type === 'float' || $type === 'double') {
			return (float)$value;
		} elseif($type === 'str' || $type === 'string') {
			return (string)$value;
		} elseif($type === 'array') {
			return (array)$value;
		} elseif($type === DateTimeInterface::class) {
			return new DateTime($value);
		} elseif($type === 'mixed') {
			if(is_numeric($value)) return (float)$value;
			elseif(is_array($value) && $value['id']) {
				if(is_numeric($value['id'])) return (int)$value['id'];
				else return (string)$value['id'];
			} else return $value;
		} else {
			throw new Exception('Failed to convert. Unexpected format: ' . $type);
		}
	}

	public function fillObject(object $object, object $source, callable $resolver = null): void {
		foreach(get_object_vars($source) as $key => $value) {
			if($resolver) [$key, $value] = call_user_func($resolver, $key, $value, $this);
			$setter = "set{$key}";
			if(method_exists($object, $setter)) {
				call_user_func([$object, $setter], $value);
			} elseif(property_exists($object, $key)) {
				$object->$key = $value;
			}
		}
	}

	public function getValue(object $object, string $property, mixed $defaultValue = null): mixed {
		if(self::isPropertyDefined($object, $property)) {
			return $object->$property;
		} else {
			return $defaultValue;
		}
	}

	public static function isPropertyDefined(object $object, string $property): bool {
        return key_exists($property, (array)$object);
	}

    public function doIfDefined(object $object, string $property, callable $callable): void {
        if(self::isPropertyDefined($object, $property)) {
            $value = $object->$property;
            call_user_func($callable, $value);
        }
    }

    public static function getEnumCases(string $enumClass, bool $named = true): array {
        if(!class_exists($enumClass)) throw new RuntimeException('Unknown enum: ' . $enumClass);
        $rc = new ReflectionClass($enumClass);
        if(!$rc->isEnum()) throw new RuntimeException('Not enum: ' . $enumClass);
        $cases = [];
        foreach($enumClass::cases() as $case) {
            if($named) {
                $cases[ $case->name ] = $case->value;
            } else {
                $cases[] = $case->value;
            }
        }
        return $cases;
    }

}
