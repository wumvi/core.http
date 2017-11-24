<?php
declare(strict_types=1);

namespace Core\Http\Di;

use Core\Http\Di\Exception\DiException;
use Core\Http\InitSettings;
use Symfony\Component\Yaml\Yaml;

/**
 * DI
 */
class Di
{
    /**  */
    public const SITE_ROOT = '{site.root}';

    /**  */
    public const SITE_HOST = '{site.host}';

    /**  */
    public const RUN_MODE = '{run.mode}';

    /** Блок с ресурсами */
    private const BLOCK_RAW = 'raw';

    /** Блок с классами */
    private const BLOCK_CLASS = 'class';

    /** Блок с Include */
    private const BLOCK_INCLUDE = 'include';

    private const ATTRIBUTE_PARAM = 'param';
    private const ATTRIBUTE_CLASS = 'class';

    /**
     * @var array Данные файла настроек
     */
    private $data = [];

    /**
     * @var array Закешированные объекты
     */
    private $objList = [];

    /**
     * @var InitSettings Базовые настройки
     */
    private $initSettings;

    /**
     * @var string Режим запуска
     */
    private $runMode = '';

    /**
     * Инициализирует настройки.
     *
     * @param string $diFile Yaml файл с настройками
     * @param InitSettings $initSettings Базовые настройки
     * @param string $runMode Режим запуска
     *
     * @throws DiException
     */
    public function initDi(string $diFile, InitSettings $initSettings, string $runMode): void
    {
        $filename = $initSettings->getSiteRoot() . $diFile;
        if (!is_readable($filename)) {
            throw new DiException('File "' . $filename .'" not found', DiException::FILE_NOT_FOUND);
        }

        $this->data = Yaml::parse(file_get_contents($filename));
        $extend = $this->data[self::BLOCK_INCLUDE] ?? '';
        if ($extend) {
            $filename = dirname($filename) . '/' . $extend;
            if (!is_readable($filename)) {
                throw new DiException('File "' . $filename .'" not found', DiException::FILE_NOT_FOUND);
            }

            $advData = Yaml::parse(file_get_contents($filename));
            if (isset($advData[self::BLOCK_CLASS])) {
                $this->data[self::BLOCK_CLASS] = array_merge(
                    $advData[self::BLOCK_CLASS],
                    $this->data[self::BLOCK_CLASS] ?? []
                );
            }

            if (isset($advData[self::BLOCK_RAW])) {
                $this->data[self::BLOCK_RAW] = array_merge(
                    $advData[self::BLOCK_RAW],
                    $this->data[self::BLOCK_RAW] ?? []
                );
            }
        }

        $this->initSettings = $initSettings;
        $this->runMode = $runMode;
    }

    /**
     * Возвращает ресурс по имени
     *
     * @param string $name Название ресурса
     *
     * @return mixed Ресурс
     *
     * @throws Exception\DiException
     *
     * @
     */
    private function parseResRaw(string $name)
    {
        if (!array_key_exists($name, $this->data[self::BLOCK_RAW])) {
            throw new Exception\DiException(
                'Res ' . $name . ' not found in DI',
                DiException::RES_DATA_NOT_FOUND
            );
        }

        return $this->data[self::BLOCK_RAW][$name];
    }

    /**
     * Парсинг конфига
     *
     * @param mixed $val Значение
     *
     * @return mixed Результат парсинга
     */
    protected function parse($val)
    {
        if (!$val) {
            return $val;
        }

        if (is_string($val)) {
            switch ($val[0]) {
                case '@':
                    return $this->make(substr($val, 1));
                case '#':
                    return $this->parseResRaw(substr($val, 1));
            }
        } elseif (is_array($val)) {
            $param = [];
            foreach ($val as $key => $item) {
                $param[$key] = $this->parse($item);
            }

            return $param;
        }

        return $val;
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception\DiException
     */
    public function getRes(string $name)
    {
        $val = $this->data[self::BLOCK_RAW][$name] ?? null;
        if ($val === null) {
            throw new Exception\DiException(
                'Res ' . $name . ' not found in DI',
                DiException::RES_DATA_NOT_FOUND
            );
        }

        return $val;
    }

    /**
     * Возвращает объект по названию
     *
     * @param string $name Название объекта из di.yaml
     *
     * @return mixed Запрашиваемый объект
     *
     * @throws Exception\DiException
     */
    public function make(string $name)
    {
        switch ($name) {
            case self::RUN_MODE:
                return $this->runMode;
            case self::SITE_HOST:
                return $this->initSettings->getHttpHost();
            case self::SITE_ROOT:
                return $this->initSettings->getSiteRoot();
        }

        // Если объект найден, то возвращаем его
        if (array_key_exists($name, $this->objList)) {
            return $this->objList[$name];
        }



        // Если имя не найдено
        if (!array_key_exists($name, $this->data[self::BLOCK_CLASS])) {
            throw new Exception\DiException(
                'Resource "' . $name . '" not found in DI.yaml',
                DiException::CLASS_NAME_NOT_FOUND
            );
        }

        // Если это не массив, то это неправильный формат
        if (!is_array($this->data[self::BLOCK_CLASS][$name])) {
            throw new Exception\DiException(
                'Name [' . $name . '] is not array DI',
                DiException::BAD_FORMAT
            );
        }

        // получаем class
        $className = $this->data[self::BLOCK_CLASS][$name][self::ATTRIBUTE_CLASS] ?? '';
        if (!$className) {
            throw new Exception\DiException(
                'Name [' . $name . '] don\'t have class name',
                DiException::CLASS_NOT_FOUND
            );
        }

        // Парсим параметры для создания
        $param = '';
        if (isset($this->data[self::BLOCK_CLASS][$name][self::ATTRIBUTE_PARAM])) {
            $param = $this->parse($this->data[self::BLOCK_CLASS][$name][self::ATTRIBUTE_PARAM]);
        }

        // Если это не массив, то нужно создать массив
        if (!is_array($param)) {
            $param = [$param];
        }

        // $this->objList[$name] = new $className(...$param);
        $refClass = new \ReflectionClass($className);
        $this->objList[$name] = $refClass->newInstanceArgs($param);

        return $this->objList[$name];
    }
}
