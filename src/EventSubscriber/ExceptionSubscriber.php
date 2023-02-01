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

class ExceptionSubscriber implements EventSubscriberInterface {

    protected bool $isDev;

	public function __construct(KernelInterface $kernel) {
        $this->isDev = $kernel->getEnvironment() === 'dev';
	}

	public function onKernelException(ExceptionEvent $event) {

		$throwable = $event->getThrowable();

		$code = $throwable instanceof Error ? 500 : $throwable->getCode();

		if($code < 400 || $code > 499) $code = 400;

		if($throwable instanceof BadRequestHttpException) $code = 400;
		elseif($throwable instanceof AccessDeniedHttpException) $code = 403;
		elseif($throwable instanceof NotFoundHttpException) $code = 404;

		$message = $throwable->getMessage();

		if(preg_match('/^.+\\\([A-z]+).+ object not found/', $message, $m)) {
			$message = "{$m[1]} object not found";
		} elseif(preg_match('/Access Denied/i', $message)) {
			$message = 'Access Denied';
		}

		$res = [
			'error' => $message,
			'code' => $code,
		];

		if($this->isDev) {
			$res['trace'] = $throwable->getTrace();
		}

		$event->setResponse(new JsonResponse($res, $code));

	}

	public static function getSubscribedEvents(): array {
		return [ExceptionEvent::class => 'onKernelException'];
	}

}
