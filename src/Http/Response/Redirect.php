<?php
declare(strict_types = 1);

namespace Core\Http\Http\Response;

/**
 * Редирект Response
 */
class Redirect extends Response
{
    /**
     * Код http для постоянного редиректа
     */
    public const HTTP_CODE_PERMANENT = 301;

    /**
     * Код http для временного редиректа
     */
    public const HTTP_CODE_TEMPORARY = 302;

    /**
     * @inheritdoc
     */
    public function get(): string
    {
        return '';
    }
}
