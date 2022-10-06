<?php

namespace Yabx\RestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RestBundle extends Bundle {

	public function getPath(): string {
		return dirname(__DIR__);
	}

}
