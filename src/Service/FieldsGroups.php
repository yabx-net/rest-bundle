<?php

namespace Yabx\RestBundle\Service;

class FieldsGroups {

    protected array $groups = [];
    protected static self $instance;

    public static function getInstance(): self {
        return self::$instance ?? self::$instance = new self;
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function initGroups(array|string $groups): void {
        if(is_string($groups)) $groups = explode(',', $groups);
        $this->groups = array_values(array_unique(['main', ...$groups]));
    }

    public function mergeGroups(array|string $groups): void {
        if(is_string($groups)) $groups = explode(',', $groups);
        $groups = [...$this->groups, ...$groups];
        foreach ($groups as $idx => $group) {
            if(str_starts_with($group, '-')) {
                $group = ltrim($group, '-');
                unset($groups[$idx]);
                while (($idx = array_search($group, $groups)) !== false) {
                    unset($groups[$idx]);
                }
            }
        }
        $this->groups = array_values(array_unique($groups));
    }

}
