<?php
declare(strict_types=1);

namespace Core\Http;

use Core\Http\Di\Di;
use Core\Http\Di\Exception\DiException;
use Core\Http\Exception\RouteException;
use Core\Http\Http\Exception\Error4xx;
use Core\Http\Http\Exception\Error4xx as Error4xxException;
use Core\Http\Http\Response\Error4xx as Error4xxResponse;
use Core\Http\Http\Response\Redirect;

/**
 * Абстрактный базовй класс контроллера
 */
abstract class RootController implements MinimalControllerInterface
{
    /** @var Init */
    protected $init;

    /** @var string */
    private $baseDir = '';

    /** @var string Режим запуска */
    private $runMode;

    /**
     * @var Di|null
     */
    private $di = null;

    /**
     * @var string
     */
    private $routeName;

    /**
     * Constructor.
     *
     * @param string $routeName
     * @param Init $init
     * @param string $runMode Режим запуска
     */
    public function __construct(string $routeName, Init $init, string $runMode)
    {
        $this->init = $init;
        $this->runMode = $runMode;
        $this->routeName = $routeName;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @return Di
     * @throws DiException
     */
    public function getDi(): Di
    {
        if ($this->di === null) {
            $this->di = new Di();
            $confFilename = sprintf('conf/di-%s.yaml', $this->runMode);
            $this->di->initDi($confFilename, $this->init->getSettings(), $this->runMode);
        }

        return $this->di;
    }

    /**
     * Возвращает режим разработки.
     *
     * @return string
     */
    public function getRunMode()
    {
        return $this->runMode;
    }

    /**
     * @param string $method
     * @param array $matches
     *
     * @return mixed
     */
    public function run(string $method, array $matches)
    {
        $this->preCallAction($method);

        return call_user_func_array([$this, $method], $matches);
    }

    abstract protected function preCallAction(string $method): void;

    /**
     * Строим Полный путь из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array $variables Переменные для URL
     * @param array $query Данные для GET запроса
     *
     * @return string URL если запись найдена в route.yaml иначе пустая строка
     * @throws \Exception
     */
    public function getAbsoluteUrl($name, array $variables = [], $query = []): string
    {
        $request = new RequestHttp();
        $path = $this->getRoutePath($name, $variables, $query);

        return $request->getAbsolutePath($path);
    }

    /**
     * Строим Url Path из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array $variables Переменные для URL
     * @param array $query Переменные для GET параметров
     *
     * @return string URL если запись найдена в route.yaml иначе пустая строка
     *
     * @throws RouteException
     */
    public function getRoutePath($name, array $variables = [], array $query = []): string
    {
        if (in_array($name, [Init::ROUTE_FIELD_VARS])) {
            $msg = vsprintf('Name "%s" is reserved', [$name,]);
            throw new RouteException($msg, RouteException::NAME_IS_RESERVED);
        }

        if (!isset($this->init->getRouteData()[$name])) {
            throw new RouteException('Route not found', RouteException::ROUTE_NOT_FOUND);
        }

        $urlGetParam = '';
        if ($query) {
            $urlGetParam .= '?' . http_build_query($query);
        }

        $item = $this->init->getRouteData()[$name];
        if (!$variables) {
            return trim($item['regexp'], ' ^$') . $urlGetParam;
        }

        $url = $item['regexp'];
        foreach ($variables as $key => $varItem) {
            $url = str_replace('{' . $key . '}', $varItem, $url);
        }

        return trim($url, ' ^$') . $urlGetParam;
    }

    /**
     * @return string
     */
    public function getConfDir(): string
    {
        return $this->baseDir . 'conf/';
    }

    /**
     * @return string
     */
    public function getDataDir(): string
    {
        return $this->baseDir . 'data/';
    }

    /**
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * @param string $baseDir
     */
    public function setBaseDir(string $baseDir): void
    {
        $this->baseDir = $baseDir;
    }

    /**
     * Директория с шаблонами
     *
     * @return string
     */
    public function getTplDir(): string
    {
        return $this->baseDir . 'tpl/';
    }

    /**
     * Вовращает директорию для кеширования
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return  '/tmp/cache/' . $this->init->getSettings()->getHttpHost() . '/';
    }

    /**
     * @param string $msg
     * @param int $code
     *
     * @throws Error4xxException
     */
    public function invokeError4xx(string $msg = '', int $code = Error4xxResponse::HTTP_CODE_NOT_FOUND): void
    {
        $msg = vsprintf('Http %s code', [$code,]);
        throw new Error4xxException($msg, $code);
    }

    /**
     * Редирект на другой URL
     *
     * @param string $url URL для редиректа
     * @param int $code Код редиректа. see Redirect::REDIRECT_*
     *
     * @return Redirect;
     */
    public function redirectToUrl(string $url, int $code = Error4xxResponse::HTTP_CODE_NOT_FOUND): Redirect
    {
        header('Location: ' . $url, true, $code);

        return new Redirect();
    }

    /**
     * Строим URL из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array $variables Переменные для URL
     * @param int $code Код редиректа. see Redirect::REDIRECT_*
     *
     * @return Redirect
     *
     * @throws \Exception
     */
    public function redirectToRoute(
        string $name,
        array $variables = [],
        int $code = Redirect::HTTP_CODE_PERMANENT
    ): Redirect {
        $url = $this->getRoutePath($name, $variables);
        header('Location: ' . $url, true, $code);

        return new Redirect();
    }

    public function initRender(\Twig_Environment $twig): void
    {
        $twig->addFunction(new \Twig_SimpleFunction('url', [$this, 'getAbsoluteUrl']));
        $twig->addFunction(new \Twig_SimpleFunction('route', [$this, 'getRoutePath']));
    }

    abstract public function preRender(
        \Twig_Environment $twig,
        \Twig_Loader_Filesystem $loader,
        string &$template,
        array &$variables
    ): void;
}
