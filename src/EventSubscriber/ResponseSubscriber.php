<?php

namespace Yabx\RestBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface {

	public function onResponseEvent(ResponseEvent $event) {
		$headers = $event->getResponse()->headers;
		$headers->set('Access-Control-Allow-Origin', '*');
		if($event->getRequest()->getMethod() === 'OPTIONS') {
			$headers->set('Access-Control-Allow-Methods', '*');
			$headers->set('Access-Control-Allow-Headers', ['*', 'Authorization']);
			$headers->set('Access-Control-Allow-Credentials', 'true');
			$headers->set('Access-Control-Max-Age', '1728000');
			$headers->set('Cache-Control', 'no-cache, must-revalidate');
		}
	}

	public static function getSubscribedEvents(): array {
		return [ResponseEvent::class => 'onResponseEvent'];
	}

}
