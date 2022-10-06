<?php

namespace Yabx\RestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class RestController extends AbstractController {

	public function result($data, int $httpCode = Response::HTTP_OK, array $groups = []): JsonResponse {
		$groups[] = 'main';
		return $this->json(['result' => $data], $httpCode, [], ['groups' => $groups]);
	}

	public function error(string $message, int $httpCode = Response::HTTP_BAD_REQUEST): JsonResponse {
		return $this->json(['error' => $message], $httpCode);
	}

}
