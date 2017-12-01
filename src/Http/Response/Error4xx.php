<?php
declare(strict_types=1);

namespace Core\Http\Http\Response;

/**
 * 4xx Error Response
 */
class Error4xx extends Response
{
    public const HTTP_CODE_NOT_FOUND = 404;

    /**
     * Доступ запрещен
     */
    public const HTTP_CODE_FORBIDDEN = 403;

    /**
     * Неверный запрос
     */
    public const HTTP_CODE_BAD_REQUEST = 400;

    /**
     * @var int Код ошибки
     */
    protected $code;

    /**
     * Constructor
     *
     * @param mixed $code Кот ошибки
     */
    public function __construct($code)
    {
        $this->code = (int)$code;
    }

    /**
     * Возвращет код ошибки
     *
     * @return int Код ошибки
     */
    public function get(): int
    {
        return $this->code;
    }

    /**
     * Возвращает код ошибки запроса
     *
     * @return int Возвращает код ошибки запроса
     */
    public function getHttpStatus(): int
    {
        return self::HTTP_CODE_NOT_FOUND;
    }
}
