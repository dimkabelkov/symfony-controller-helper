<?php

namespace Dimkabelkov\ControllerHelper\Type;

abstract class AbstractType
{
    protected $id = null;

    public function __construct(?string $id = null)
    {
        $this->id = $id;
    }

    public function getId():? string
    {
        return $this->id;
    }
}