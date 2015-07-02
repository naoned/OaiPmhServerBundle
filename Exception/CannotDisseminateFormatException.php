<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class CannotDisseminateFormatException extends OaiPmhServerException implements ExceptionInterface
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'The metadata format identified by the value given for the metadataPrefix argument
                        is not supported by the item or by the repository.';
        }
        return parent::__construct($message, $code, $previous);
    }
}
