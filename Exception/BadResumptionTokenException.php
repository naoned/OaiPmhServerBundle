<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class BadResumptionTokenException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'The value of the resumptionToken argument is invalid or expired.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
