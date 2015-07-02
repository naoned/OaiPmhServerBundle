<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class IdDoesNotExistException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'The value of the identifier argument is unknown or illegal in this repository.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
