<?php
declare(strict_types=1);

namespace Core\Http;
use Core\Http\Model\FileUpload;

/**
 * Получение переменных и работы с массивами GET или POST
 *
 * @author Козленко В.Л.
 */
class RequestHttp
{
    /**
     * Возвращает GET переменную
     *
     * @param string $name название переменной
     * @param mixed $default значение по умолчанию, если переменной нет
     *
     * @return string Значение переменной
     */
    public function get(string $name, string $default = ''): string
    {
        return $_GET[$name] ?? $default;
    }

    /**
     * Возвращает массив из URL
     *
     * @param string $name Имя массива
     * @param array $default Значение по умолчанию
     *
     * @return array Массив данных
     */
    public function getArray(string $name, array $default = []): array
    {
        return $_GET[$name] ?? $default;
    }

    /**
     * Возвращает значение переменной из GET массива
     *
     * @param string $name название переменной
     * @param integer $default значение по умолчанию, если переменной нет или опеределена как пустая
     *
     * @return integer значение переменной
     */
    public function getInt(string $name, int $default = 0): int
    {
        $val = $_GET[$name] ?? $default;
        return $val === '' ? $default : (int)$val;
    }

    /**
     * Возвращает значение переменной из POST массива и преобразует в int
     *
     * @param string $name название переменной
     * @param integer $default значение по умолчанию, если переменной нет или опеределена как пустая
     *
     * @return integer значение переменной
     */
    public function postInt(string $name, int $default = 0): int
    {
        $val = $_POST[$name] ?? $default;
        return $val === '' ? $default : (int)$val;
    }

    /**
     * Возвращает POST переменную
     *
     * @param string $name Название параметра
     * @param string $default Значение по умолчанию, если переменной нет
     *
     * @return string Значение
     */
    public function post(string $name, string $default = ''): string
    {
        return $_POST[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param array $default
     *
     * @return array
     */
    public function postArray(string $name, array $default = []): array
    {
        return $_POST[$name] ?? $default;
    }

    /**
     * Возвращает целочисленное значение переменной из GET или POST
     * С начала проверяет GET а потом POST
     *
     * @param string $name Имя переменной
     * @param int $default Значение по умолчанию, если переменная не задана
     *
     * @return int Значение
     */
    public function getVarInt(string $name, int $default = 0): int
    {
        $val = $_GET[$name] ?? ($_POST[$name] ?? $default);
        return (int)$val;
    }

    /**
     * Возвращает значение переменной из GET или POST
     * С начала проверяет GET а потом POST
     *
     * @param string $name Имя переменной
     * @param string $default Значение по умолчанию, если переменная не задана
     *
     * @return string Значение
     */
    public function getVar(string $name, string $default = ''): string
    {
        return $_GET[$name] ?? ($_POST[$name] ?? $default);
    }

    /**
     * Возвращает значение переменной из GET или POST
     * С начала проверяет GET а потом POST
     *
     * @param string $name Имя параметра
     * @param array $default Значение по умолчанию, если переменная не задана
     *
     * @return array Значение
     */
    public function getVarArray(string $name, array $default = []): array
    {
        return $_GET[$name] ?? ($_POST[$name] ?? $default);
    }

    /**
     * Возвращает безопастную строки из переменных POST.
     * Переменная обрабатывается htmlspecialchars
     *
     * @param string $name Имя параметра
     * @param string $default Значение по умолчанию, если переменная не передана
     * @param integer $quotes Какие использовать ковычки см. ENT_COMPAT ENT_QUOTES ENT_NOQUOTES ENT_IGNORE
     *
     * @return string Обработанные данные
     */
    public function postSafe(string $name, string $default = '', $quotes = ENT_COMPAT): string
    {
        return htmlspecialchars(self::post($name, $default), $quotes);
    }

    /**
     * Возвращает TRUE, если запрос типа POST, иначе FALSE
     *
     * @return bool Post запрос это или нет
     */
    public function isPost(): bool
    {
        return strlen($_SERVER['REQUEST_METHOD']) === 4;
    }

    /**
     * Возвращает TRUE, если производится загрузка файла, иначе FALSE
     *
     * @return bool Идёт ли загрузка файла
     */
    public function isFileUpload(): bool
    {
        return count($_FILES) != 0;
    }

    /**
     * Возвращает данные по загружаемому файлу
     *
     * @param string $key Ключ файла
     *
     * @return FileUpload|null Данные по файлу
     */
    public function getFileInfo(string $key): ?FileUpload
    {
        return isset($_FILES[$key]) ? new FileUpload($_FILES[$key]) : null;
    }

    /**
     * Возвращает хост
     *
     * @return string хост
     */
    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Возвращает относительный путь
     *
     * @return string Относительный путь
     */
    public function getPath(): string
    {
        return $_SERVER['DOCUMENT_URI'];
    }

    /**
     * Возвращает протокол запроса
     *
     * @return string Протокол запроса
     */
    public function getProtocol(): string
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    }

    /**
     * Возвращает полный URL к указанному Path
     *
     * @param string $path Относительный путь
     *
     * @return string Абсолютный путь
     */
    public function getAbsolutePath(string $path): string
    {
        return $this->getProtocol() . '://' . $_SERVER['HTTP_HOST'] . $path;
    }

    /**
     * Возвращает полный канонический URL
     *
     * @return string string Полный канонический URL
     */
    public function getCanonicalUrl(): string
    {
        return $this->getProtocol() . '://' . $_SERVER['HTTP_HOST'] . $this->getPath();
    }

    /**
     * Возвращает IP запроса
     *
     * @return string IP запроса
     */
    public function getIp(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Возвращает флаг, если он поддерживается браузером
     *
     * @param string $accept Название значения
     *
     * @return bool Флаг поддержки
     */
    public function isAcceptHas(string $accept): bool
    {
        return strpos($_SERVER['HTTP_ACCEPT'], $accept) !== false;
    }

    /**
     * Возвращает время запроса
     *
     * @return int Время запроса
     */
    public function getTime(): int
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * Возвращает Ajax request это или нет
     *
     * @return bool Ajax request or not
     */
    public function isAjax(): bool
    {
        $request = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return !empty($request) && strtolower($request) === 'xmlhttprequest';
    }


    /**
     * Возвращает все cookies
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $_COOKIE;
    }

    /**
     * Возвращает значение cookie
     *
     * @param string $name Имя cookie
     * @param string $default Значение по умолчанию
     *
     * @return string Значение
     */
    public function getCookie(string $name, string $default = ''): string
    {
        return $_COOKIE[$name] ?? $default;
    }
}
