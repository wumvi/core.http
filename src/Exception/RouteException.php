<?php

namespace Core\Http\Exception;

class RouteException extends \Exception
{
    public const BAD_FORMAT = 1;
    public const BAD_INTERFACE = 2;
    public const METHOD_NOT_FOUND = 3;
    public const GLOBAL_VAR_NOT_FOUND = 4;
    public const CLASS_NOT_FOUND = 5;
    public const ERROR_TO_OPEN_FILE = 6;
    public const CONFIG_INVALID = 7;
    public const REGEXP_NOT_FOUND = 8;
    public const ROUTE_NOT_FOUND = 9;
    public const NAME_IS_RESERVED = 10;
}
