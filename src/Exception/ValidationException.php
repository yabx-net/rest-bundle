<?php

namespace Yabx\RestBundle\Exception;

use Exception;

class ValidationException extends Exception {

    protected string $key;
    protected string $name;
    protected string $error;

    public function __construct(string $key, string $name, string $error) {
        $this->key = $key;
        $this->name = $name;
        $this->error = $error;
        parent::__construct($name . ': ' . $error, 400);
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getError(): string {
        return $this->error;
    }

}
