<?php
declare(strict_types=1);

namespace Core\Http\Http\Response;

/**
 * Базовый Response
 */
abstract class Response
{
    /**
     * Все нормаьно
     */
    public const HTTP_CODE_OK = 200;

    /**
     * Ошибка сервера
     */
    public const HTTP_CODE_INTERNAL_ERROR = 500;

    /**
     * @var int Статус ответа
     */
    protected $httpStatus = self::HTTP_CODE_OK;

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * @var array Масси заголовков
     */
    private $headers = [];

    /**
     * Возвращает данные ответа
     *
     * @return mixed
     */
    abstract public function get();

    /**
     * Возвращает статус ответа
     *
     * @return int Статус ответа
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Добавляет cookie для установки
     *
     * @param string $name Имя куки
     * @param mixed $value Значение
     * @param int $lifeTime Время жизни
     * @param string $path Путь
     * @param bool $isHttps Cookie только для HTTPS
     * @param string $domain Домен для cookie
     * @param boolean $httpOnly Cookie только для HTTP
     */
    public function addCookie(
        string $name,
        $value,
        int $lifeTime,
        string $path = '/',
        bool $isHttps = true,
        string $domain = '',
        bool $httpOnly = true
    ): void {
        $domain = $domain ?: $_SERVER['HTTP_HOST'];
        $this->cookies[$name] = [
            $name,
            $value,
            $_SERVER['REQUEST_TIME'] + $lifeTime,
            $path,
            $domain,
            $isHttps,
            $httpOnly,
        ];
    }

    /**
     * Удаляет куку
     *
     * @param string $name Название
     */
    public function removeCookie(string $name): void
    {
        $this->cookies[$name] = null;
    }

    /**
     * Возвращает массив, какие cookie надо установить
     *
     * @return array Массив данных для cookie
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
