<?php

namespace Yabx\RestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Yabx\RestBundle\Service\FieldsGroups;

class RestController extends AbstractController {

    public function result($data, int $httpCode = Response::HTTP_OK, array $groups = []): JsonResponse {
        $fg = FieldsGroups::getInstance();
		$fg->mergeGroups($groups);
		return $this->json(['result' => $data], $httpCode, [], ['groups' => $fg->getGroups()]);
	}

	public function error(string $message, int $httpCode = Response::HTTP_BAD_REQUEST): JsonResponse {
		return $this->json(['error' => $message], $httpCode);
	}

}
