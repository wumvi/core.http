<?php
declare(strict_types = 1);

namespace Core\Http;

use Core\Model\Read;

/**
 * Начальные настройки
 *
 * @method string getSiteRoot() Возвращает корень сайта
 * @method string getDocumentUri() Возвращает Uri сайта
 * @method string getHttpHost() Возвращает хост сайта
 */
class InitSettings extends Read
{
    /** Корень сайта */
    const SITE_ROOT = 'siteRoot';

    /** URI запроса */
    const DOCUMENT_URI = 'documentUri';

    /** Хост */
    const HTTP_HOST = 'httpHost';
}
