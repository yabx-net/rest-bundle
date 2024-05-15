<?php

namespace Yabx\RestBundle\EventSubscriber;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Error;
use Yabx\RestBundle\Exception\ValidationException;

class ExceptionSubscriber implements EventSubscriberInterface {

    protected bool $isDev;

	public function __construct(KernelInterface $kernel) {
        $this->isDev = $kernel->getEnvironment() === 'dev';
	}

	public function onKernelException(ExceptionEvent $event): void {

		$throwable = $event->getThrowable();

		$code = $throwable instanceof Error ? 500 : $throwable->getCode();

		if($code < 400 || $code > 499) $code = 400;

		if($throwable instanceof BadRequestHttpException) $code = 400;
		elseif($throwable instanceof AccessDeniedHttpException) $code = 403;
		elseif($throwable instanceof NotFoundHttpException) $code = 404;

		$message = $throwable->getMessage();

		if(preg_match('/^.+\\\([A-z]+).+ object not found/', $message, $m)) {
			$message = "{$m[1]} object not found";
		} elseif(preg_match('/access denied/i', $message)) {
            $message = 'Access Denied';
            $code = 403;
        } elseif (str_contains($message, 'authentication')) {
            $code = 401;
        }

		$res = [
			'error' => $message,
			'code' => $code,
		];

        if($throwable instanceof ValidationException) {
            $res['validation'] = [
                'key' => $throwable->getKey(),
                'name' => $throwable->getName(),
                'error' => $throwable->getError(),
            ];
        }

		if($this->isDev) {
			$res['trace'] = $throwable->getTrace();
		}

		$event->setResponse(new JsonResponse($res, $code));

	}

	public static function getSubscribedEvents(): array {
		return [ExceptionEvent::class => 'onKernelException'];
	}

}
