<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class UndefinedVerbException extends \BadMethodCallException implements ExceptionInterface
{
}
