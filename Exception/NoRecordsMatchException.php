<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class NoRecordsMatchException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'The combination of the values of the from, until, set and metadataPrefix arguments results
                        in an empty list.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
