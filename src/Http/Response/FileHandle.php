<?php
declare(strict_types=1);

namespace Core\Http\Http\Response;

/**
 * File Response
 */
class FileHandle extends Response
{
    /**
     * @var resource|string Данные о файле
     */
    protected $data;

    /**
     * @var string Content type
     */
    protected $contentType;

    /**
     * Contructor
     *
     * @param resource|string $data handle на файл или имя файла
     * @param string $contentType Заголовок Content-Type
     *
     * @throws \Exception Если данные не заданы
     */
    public function __construct($data, $contentType = '')
    {
        if (!$data) {
            throw new \Exception('FileHandle $data can\'t be NULL');
        }
        $this->data = $data;
        $this->contentType = $contentType;

        $this->addHeader('Content-Type', $contentType);
    }

    /**
     * Получаем Content Type
     *
     * @return string Content Type
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Получаем данные
     *
     * @return bool|resource|string Данные или handle на них, false - если их нет
     *
     * @throws \Exception
     */
    public function get()
    {
        if (gettype($this->data) == 'resource') {
            return $this->data;
        }

        if (!is_string($this->data)) {
            throw new \Exception('Unsupported type ' . gettype($this->data));
        }

        if (!is_file($this->data)) {
            return false;
        }

        return fopen($this->data, 'r');
    }
}
