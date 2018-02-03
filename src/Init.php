<?php
declare(strict_types=1);

namespace Core\Http;

use Core\Http\Exception\RouteException;
use Core\Http\Http\Exception\Error4xx as Error4xxException;
use Core\Http\Http\Response\Error4xx as Error4xxResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Первичная инициализация
 */
class Init
{
    /** @const Запуск на компе разработчика */
    const DEV_MODE_DEV = 'dev';

    /** @const Запуск на продакшене */
    const DEV_MODE_PROD = 'prod';

    const ROUTE_INDEX_KEY = 'index';

    const ROUTE_FIELD_CONTROLLER = 'controller';

    const ROUTE_FIELD_REGEXP = 'regexp';
    const ROUTE_FIELD_AJAX = 'ajax';
    const ROUTE_FIELD_VARS = 'vars';

    /** @var array */
    private $routeData = [];

    /** @var array Переменные для автоподстановки в URL */
    private $vars = [];

    /** @var string Режим запуска */
    private $runMode;

    /** @var InitSettings Начальные настройки */
    private $initSettings;

    /**
     * Constructor.
     *
     * @param string $runMode Режим запуска
     * @param InitSettings $initSettings Начальные настройки
     */
    public function __construct(string $runMode, InitSettings $initSettings)
    {
        $this->runMode = $runMode;
        $this->initSettings = $initSettings;
    }

    /**
     * @return array
     */
    public function getRouteData(): array
    {
        return $this->routeData;
    }

    public function getSettings(): InitSettings
    {
        return $this->initSettings;
    }

    /**
     * @param string $configFile
     *
     * @return mixed
     *
     * @throws Error4xxException
     * @throws RouteException
     * @throws \Exception
     */
    public function initRoute(string $configFile)
    {
        if (!is_readable($configFile)) {
            $msg  = vsprintf('File "%s" not found', [$configFile,]);
            throw new RouteException($msg, RouteException::ERROR_TO_OPEN_FILE);
        }

        $this->routeData = Yaml::parse(file_get_contents($configFile));
        if (!$this->routeData) {
            $msg = vsprintf('Config "%s" is invalid', [$configFile,]);
            throw new RouteException($msg, RouteException::CONFIG_INVALID);
        }

        $routeData = $this->routeData;

        // Если запрос был сделан на главную страницу и такой контроллер есть, то вызываем его
        $indexController = $routeData[self::ROUTE_INDEX_KEY][self::ROUTE_FIELD_CONTROLLER] ?? '';
        if ($indexController !== '' && $this->initSettings->getDocumentUri() == '/') {
            $this->initSafeAjaxRequest($routeData[self::ROUTE_INDEX_KEY][self::ROUTE_FIELD_AJAX] ?? false);

            return $this->makeController(self::ROUTE_INDEX_KEY, $indexController, []);
        }

        unset($routeData[self::ROUTE_INDEX_KEY]);

        $this->vars = [];
        if (isset($routeData[self::ROUTE_FIELD_VARS])) {
            $this->vars = $routeData[self::ROUTE_FIELD_VARS];
            unset($routeData[self::ROUTE_FIELD_VARS]);
        }

        // Иначем бегаем по всем роутингам и ищем подходящий
        foreach ($routeData as $routeName => $item) {
            if (!isset($item[self::ROUTE_FIELD_REGEXP]) || !$item[self::ROUTE_FIELD_REGEXP]) {
                $message = vsprintf('Field regex not found in route "%s"', [$routeName,]);
                throw new RouteException($message, RouteException::REGEXP_NOT_FOUND);
            }

            $regexp = $item[self::ROUTE_FIELD_REGEXP];

            if (isset($item[self::ROUTE_FIELD_VARS])) {
                foreach ($item[self::ROUTE_FIELD_VARS] as $varName => $varVal) {
                    if ($varVal[0] === '@') {
                        $globalVarName = substr($varVal, 1);
                        if (!isset($this->vars[$globalVarName])) {
                            $message = vsprintf('Global var "%s" not found', [$globalVarName,]);
                            throw new RouteException($message, RouteException::GLOBAL_VAR_NOT_FOUND);
                        }
                        $varVal = $this->vars[$globalVarName];
                    }

                    $regexp = str_replace('{' . $varName . '}', $varVal, $regexp);
                }
            }

            if (preg_match('#' . $regexp . '#u', $this->initSettings->getDocumentUri(), $matches)) {
                $this->initSafeAjaxRequest($item[self::ROUTE_FIELD_AJAX] ?? false);

                return $this->makeController($routeName, $item[self::ROUTE_FIELD_CONTROLLER], $matches);
            }
        }

        // Вызываем 4xx ошибку
        throw new Error4xxException('Controller not found', Error4xxResponse::HTTP_CODE_NOT_FOUND);
    }

    /**
     * Защищает от вызова ajax запросов с других сайтов
     *
     * @param bool $isCheckAjax Проверять ли на ajax запрос
     *
     * @throws Error4xxException
     */
    private function initSafeAjaxRequest(bool $isCheckAjax): void
    {
        if (!$isCheckAjax) {
            return;
        }

        $request = new RequestHttp();
        if (!$request->isPost() || !$request->isAjax()) {
            $msg = 'Support only ajax and post request';
            throw new Error4xxException($msg, Error4xxResponse::HTTP_CODE_BAD_REQUEST);
        }
    }

    /**
     * @param string $name
     * @param string $classAndMethod
     * @param array $matches
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function makeController(string $name, string $classAndMethod, array $matches)
    {
        $data = explode('::', $classAndMethod);
        if (count($data) < 2) {
            $msg = vsprintf('Bad format "%s"', [$classAndMethod,]);
            throw new RouteException($msg, RouteException::BAD_FORMAT);
        }

        $controllerName = $data[0];
        $methodName = $data[1];

        if (!class_exists($controllerName)) {
            $msg = vsprintf('Class "%s" not found', [$controllerName,]);
            throw new RouteException($msg, RouteException::CLASS_NOT_FOUND);
        }

        /** @var MinimalControllerInterface $controller */
        $controller = new $controllerName($name, $this, $this->runMode);
        if (!($controller instanceof MinimalControllerInterface)) {
            $msg = 'Controller must be instance of MinimalControllerInterface';
            throw new RouteException($msg, RouteException::BAD_INTERFACE);
        }

        //$confFilename = sprintf('conf/di-%s.yaml', $this->runMode);
        // $controller->initDi($confFilename, $this->initSettings, $this->runMode);
        $controller->setBaseDir($this->initSettings->getSiteRoot());
        unset($data);

        if (!method_exists($controller, $methodName)) {
            $msg = vsprintf('Method "%s" in "%s" not found', [$methodName, $classAndMethod,]);
            throw new RouteException($msg, RouteException::METHOD_NOT_FOUND);
        }

        return $controller->run($methodName, $matches);
    }
}
