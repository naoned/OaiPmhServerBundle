<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class BadVerbException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'Value of the verb argument is not a legal OAI-PMH verb, the verb argument is missing, or the
                        verb argument is repeated.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
