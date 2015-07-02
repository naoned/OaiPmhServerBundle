<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class BadArgumentException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'The request includes illegal arguments, is missing required arguments, includes a repeated
            			argument, or values for arguments have an illegal syntax.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
