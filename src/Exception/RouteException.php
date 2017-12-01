<?php

namespace Core\Http\Exception;

class RouteException extends \Exception
{
    public const BAD_FORMAT = 1;
    public const BAD_INTERFACE = 2;
    public const METHOD_NOT_FOUND = 3;
    public const GLOBAL_VAR_NOT_FOUND = 4;
}
