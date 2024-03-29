<?php

namespace Yabx\RestBundle\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yabx\RestBundle\Service\FieldsGroups;

class RequestSubscriber implements EventSubscriberInterface {

	public function onRequestEvent(RequestEvent $event): void {

		$request = $event->getRequest();

        $requestMethod = $request->getMethod();
        $contentType = $request->headers->get('content-type');

		if($requestMethod === 'OPTIONS') {
            $response = new Response(null);
            $event->setResponse($response);

        } elseif($requestMethod !== 'GET' && ($contentType === 'application/x-www-form-urlencoded' || str_starts_with($contentType, 'multipart/form-data'))) {
            $data = $request->request->all();

		} elseif($body = $request->getContent() ?: $request->query->get('__payload')) {
            $data = json_decode($body, true);
            if(json_last_error()) {
                $event->setResponse(new JsonResponse(['error' => json_last_error_msg(), 'code' => -1], 400));
                return;
            }
            if(!is_array($data)) {
				$event->setResponse(new JsonResponse(['error' => 'Malformed JSON', 'code' => -2], 400));
				return;
			}

		} elseif($requestMethod === 'GET') {
			$data = $request->query->all();

		}

		if(isset($data)) {
			array_walk_recursive($data, function(&$value) {
				if(is_string($value)) $value = trim($value);
			});
			$request->request->replace($data);
            FieldsGroups::getInstance()->initGroups($data['fields'] ?? []);
		}
	}

	public static function getSubscribedEvents(): array {
		return [RequestEvent::class => ['onRequestEvent', 5000]];
	}

}
