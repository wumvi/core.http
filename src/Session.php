<?php
declare(strict_types = 1);

namespace Core\Http;

trait Session
{
    /**
     * Инициализируем сессиию
     *
     * @throws \Exception Если сессия не запустилась
     */
    private function initSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!session_start()) {
            throw new \Exception('Session not start');
        }
    }

    /**
     * Устанавливаем переменнную сессии
     *
     * @param string $name Имя сессии
     * @param mixed $value Значение сессии
     *
     * @throws \Exception Если сессия не установилась
     */
    public function setSession($name, $value)
    {
        $this->initSession();

        $_SESSION[$name] = $value;
    }

    /**
     * Получаем сессию
     *
     * @param string $name имя сессии
     *
     * @return null|mixed null если ни чего не найдено
     * @throws \Exception  Если сессия не установилась
     */
    public function getSession($name)
    {
        $this->initSession();

        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    /**
     * Удаляем сессию
     *
     * @param string $name Имя сессии
     */
    public function removeSession($name)
    {
        if (!isset($_SESSION[$name])) {
            return;
        }

        unset($_SESSION[$name]);
    }
}
