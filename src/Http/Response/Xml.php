<?php
declare(strict_types=1);

namespace Core\Http\Http\Response;

/**
 * XML Response
 */
class Xml extends Html
{
    const CONTENT_TYPE = 'text/xml; charset=utf-8';

    public function getContentType(): string
    {
        return self::CONTENT_TYPE;
    }
}
