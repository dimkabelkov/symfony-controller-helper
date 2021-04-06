<?php

namespace Dimkabelkov\ControllerHelper\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationParamsErrorException extends Exception
{
    /**
     * Exception errors.
     *
     * @var ConstraintViolationListInterface
     */
    private ConstraintViolationListInterface $errors;

    /**
     * ValidationParamsErrorException constructor.
     *
     * @param ConstraintViolationListInterface $errors
     * @param string $message
     * @param int $code
     */
    public function __construct(ConstraintViolationListInterface $errors, $message = 'Invalid params', $code = 0)
    {
        $this->errors = $errors;

        parent::__construct($message, $code);
    }

    public function getData()
    {
        $data = [];

        foreach ($this->errors as $error) {
            /** @var ConstraintViolationInterface $error */
            $property = $error->getPropertyPath();
            $data[$property] = [$error->getMessage()];
        }

        return $data;
    }
}
