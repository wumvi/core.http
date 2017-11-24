<?php
declare(strict_types = 1);

namespace Core\Http;

/**
 * Файл который загружают
 */
class FileUpload
{
    /**
     * Нет ошибки
     */
    public const ERROR_NONE = 0;

    /**
     * Название файла, на диске пользователя
     *
     * @var string
     */
    private $name;

    /**
     * Content-type файла. Может быть подделан пользователем.
     *
     * @var string
     */
    private $type;

    /**
     * Размер файла
     *
     * @var int
     */
    private $size;

    /**
     * Номер ошибки, если она есть
     *
     * @var int
     */
    private $error;

    /**
     * Временный полный путь до файла на сервере
     *
     * @var string
     */
    private $tmpName;

    /**
     * Constructor.
     *
     * @param array $fileInfo Данные файла
     */
    public function __construct(array $fileInfo)
    {
        $this->name = $fileInfo['name'];
        $this->tmpName = $fileInfo['tmp_name'];
        $this->size = $fileInfo['size'];
        $this->error = $fileInfo['error'];
        $this->type = $fileInfo['type'];
    }

    /**
     * Возвращает название файла с файловой системы пользователя. Может быть подделан пользователем.
     *
     * @return string Имя файла
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Возвращает Content-type файла. Может быть подделан пользователем.
     *
     * @return string Content-type файла
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Возвращает размер файла
     *
     * @return int Размер файла
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Возвращает временное полное название файла на сервере
     *
     * @return string Полное имя файла
     */
    public function getTmpName()
    {
        return $this->tmpName;
    }
}
