<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

class OaiPmhServerException extends \BadMethodCallException implements ExceptionInterface
{
}
