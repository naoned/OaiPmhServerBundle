<?php

namespace Naoned\OaiPmhServerBundle\Exception;

use Symfony\Component\DependencyInjection\Exception\ExceptionInterface;

abstract class OaiPmhServerException extends \Exception implements ExceptionInterface
{
}
