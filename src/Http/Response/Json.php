<?php
declare(strict_types = 1);

namespace Core\Http\Http\Response;

/**
 * Json Response
 */
class Json extends Response
{
    /**
     * @var mixed Данные для отправки
     */
    protected $dataRaw;

    /**
     * @var bool Статус запроса
     */
    protected $requestStatus;

    /**
     * @var string Сообщение об ошибке
     */
    protected $errorMsg;

    /**
     * Ошика в запросе
     */
    public const STATUS_ERROR = false;

    /**
     * Запрос прошёл удачно
     */
    public const STATUS_SUCCESS = true;

    /**
     * Constructor.
     *
     * @param mixed $data Данные для отправки
     * @param bool $requestStatus Статус запроса. См. self::STATUS_*
     * @param string $errorMsg Сообщение об ошибке, если есть
     */
    public function __construct($data, bool $requestStatus = self::STATUS_SUCCESS, string $errorMsg = '')
    {
        $this->dataRaw = $data;
        $this->requestStatus = $requestStatus;
        $this->errorMsg = $errorMsg;

        $this->addHeader('Content-Type' , 'application/json; charset=utf-8');
    }

    /**
     * Возвращает преобразованные данные
     *
     * @return string Данные
     */
    public function get() : string
    {
        return json_encode([
            '$ret' => $this->requestStatus ? 1 : 0,
            '$msg' => $this->errorMsg,
            'data' => $this->dataRaw,
        ]);
    }
}
