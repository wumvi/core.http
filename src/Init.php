<?php
declare(strict_types = 1);

namespace Core\Http;

use Core\Http\Http\Exception\Error4xx;
use Symfony\Component\Yaml\Yaml;
use Core\Http\Http\Response\Response;

/**
 * Первичная инициализация
 */
class Init
{
    /** @const Запуск на компе разработчика */
    const DEV_MODE_DEV = 'dev';

    /** @const Запуск на продакшене */
    const DEV_MODE_PROD = 'prod';

    /** @var [] */
    private $routeData = [];

    /** @var [] Переменные для автоподстановки в URL */
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
    public function getRouteData() : array
    {
        return $this->routeData;
    }

    /**
     * @param string $name
     * @param string $classAndMethod
     * @param $matches
     * @return mixed
     * @throws \Exception
     */
    public function makeController(string $name, string $classAndMethod, array $matches)
    {
        $data = explode('::', $classAndMethod);
        if (count($data) < 2) {
            throw new \Exception('Bad format ' . $classAndMethod . '  #bad-format-init');
        }

        /** @var RootController $controller */
        $controller = new $data[0]($name, $this, $this->runMode);
        $confFilename = sprintf('conf/di-%s.yaml', $this->runMode);
        $controller->initDi($confFilename, $this->initSettings, $this->runMode);
        $controller->setBaseDir($this->initSettings->getSiteRoot());

        if (!$controller) {
            throw new \Exception('Controller from ' . $classAndMethod . ' not found #cntrl-not-found-init');
        }

        $methodName = $data[1];
        unset($data);

        if (!method_exists($controller, $methodName)) {
            throw new \Exception('Method ' . $methodName . ' in ' . $classAndMethod .
                ' not found #method-not-found-init');
        }

        return $controller->run($methodName, $matches);
    }

    /**
     * Защищает от вызова ajax запросов с других сайтов
     *
     * @param bool $isCheckAjax Проверять ли на ajax запрос
     *
     * @throws Error4xx
     */
    private function initSafeAjaxRequest(bool $isCheckAjax): void
    {
        if (!$isCheckAjax) {
            return;
        }

        $request = new RequestHttp();
        if (!$request->isPost() || !$request->isAjax()) {
            throw new Error4xx('Support only ajax and post request', Response::HTTP_STATUS_BAD_REQUEST);
        }
    }

    /**
     * @param string $configFile
     *
     * @return mixed
     *
     * @throws Error4xx
     * @throws \Exception
     */
    public function initRoute(string $configFile)
    {
        $this->routeData = Yaml::parse(file_get_contents($configFile));
        if (!$this->routeData) {
            throw new \Exception('Conf "' . $configFile . '" is bad #bad-json-conf-init');
        }

        // Если запрос был сделан на главную страницу и такой контроллер есть, то вызываем его
        if (isset($this->routeData['index']['controller']) && $this->initSettings->getDocumentUri() == '/') {
            $this->initSafeAjaxRequest($this->routeData['index']['ajax'] ?? false);
            return $this->makeController('index', $this->routeData['index']['controller'], []);
        }

        unset($this->routeData['index']);

        $this->vars = [];
        if (isset($this->routeData['vars'])) {
            $this->vars = $this->routeData['vars'];
            unset($this->routeData['vars']);
        }

        // Иначем бегаем по всем роутингам и ищем подходящий
        foreach ($this->routeData as $routeName => $item) {
            if (!isset($item['regexp']) || !$item['regexp']) {
                throw new \Exception('Regexp not set in ' . $routeName);
            }

            $regexp = $item['regexp'];

            if (isset($item['vars'])) {
                foreach ($item['vars'] as $varName => $varVal) {
                    if ($varVal[0] == '@') {
                        $globalVarName = substr($varVal, 1);
                        if (!isset($this->vars[$globalVarName])) {
                            throw new \Exception('Global vars \'' . $globalVarName . '\' not found');
                        }
                        $varVal = $this->vars[$globalVarName];
                    }

                    $regexp = str_replace('{' . $varName . '}', $varVal, $regexp);
                }
            }

            if (preg_match('#' . $regexp . '#', $this->initSettings->getDocumentUri(), $matches)) {
                $this->initSafeAjaxRequest($item['ajax'] ?? false);
                return $this->makeController($routeName, $item['controller'], $matches);
            }
        }

        // Вызываем 4xx ошибку
        throw new Error4xx('Controller not found', Response::HTTP_STATUS_NOT_FOUND);
    }
}
