<?php
declare(strict_types=1);

namespace Maxlen\ConfluenceClient\Entity;


interface Hydratable
{
    /**
     * Maps array data to object
     *
     * @param mixed[] $data
     * @return self
     */
    public static function load(array $data): self;
}
