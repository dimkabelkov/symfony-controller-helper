<?php

namespace Dimkabelkov\ControllerHelper\Exception;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct($message = 'Entity not found')
    {
        parent::__construct($message);
    }
}

