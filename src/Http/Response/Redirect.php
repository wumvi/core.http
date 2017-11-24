<?php
declare(strict_types = 1);

namespace Core\Http\Http\Response;

/**
 * Редирект Response
 */
class Redirect extends Response
{
    /**
     * @inheritdoc
     */
    public function get(): string
    {
        return '';
    }
}
