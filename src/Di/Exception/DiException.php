<?php
declare(strict_types=1);

namespace Core\Http\Di\Exception;

/**
 * @codeCoverageIgnore
 */
class DiException extends \Exception
{
    /**
     *
     */
    public const CLASS_NAME_NOT_FOUND = 1;

    /**
     *
     */
    public const BAD_FORMAT = 2;

    /**
     *
     */
    public const CLASS_NOT_FOUND = 3;

    /**
     *
     */
    public const RES_DATA_NOT_FOUND = 4;

    /**
     *
     */
    public const FILE_NOT_FOUND = 5;
}
