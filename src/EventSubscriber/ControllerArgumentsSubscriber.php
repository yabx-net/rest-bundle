<?php

namespace Yabx\RestBundle\EventSubscriber;

use ReflectionClass;
use Yabx\RestBundle\Service\ObjectBuilder;
use Yabx\RestBundle\Attributes\RestRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

class ControllerArgumentsSubscriber implements EventSubscriberInterface {

	protected ObjectBuilder $builder;

	public function __construct(ObjectBuilder $builder) {
		$this->builder = $builder;
	}

	public function onControllerArgumentsEvent(ControllerArgumentsEvent $event): void {
		$args = $event->getArguments();
		$request = $event->getRequest();
		foreach($args as $idx => $arg) {
			if(!is_object($arg)) continue;
			$class = get_class($arg);
			if(class_exists($class)) {
				$rc = new ReflectionClass($class);
				if($rc->getAttributes(RestRequest::class)) {
					$args[$idx] = $this->builder->build($class, $request->request->all());
				}
			}
		}
		$event->setArguments($args);
	}

	public static function getSubscribedEvents(): array {
		return [ControllerArgumentsEvent::class => 'onControllerArgumentsEvent'];
	}

}
