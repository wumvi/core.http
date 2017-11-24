<?php
declare(strict_types = 1);

namespace Core\Http\Http\Response;

/**
 * Raw string Response
 */
class StringRaw extends Response
{
    /**
     * @var string
     */
    private $data;

    /**
     * Constructor.
     *
     * @param string $data Строка данных
     */
    public function __construct(string $data)
    {
        $this->data = $data;
        $this->addHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Возвращает данные
     *
     * @return string Данные
     */
    public function get(): string
    {
        return $this->data;
    }
}
