<?php
declare(strict_types=1);

namespace Core\Http;

use Core\Http\Di\Di;
use Core\Http\Http\Exception\Error4xx;
use Core\Http\Http\Response\Redirect;
use Core\Http\Http\Response\Response;

/**
 * Абстрактный базовй класс контроллера
 */
abstract class RootController extends Di
{
    /** @var string */
    private $baseDir = '';

    /** @var Init */
    protected $init;

    /** @var string Режим запуска */
    private $runMode;

    /**
     * Constructor.
     *
     * @param string $routeName
     * @param Init   $init
     * @param string $runMode Режим запуска
     */
    public function __construct($routeName, Init $init, string $runMode)
    {
        $this->init = $init;
        $this->runMode = $runMode;
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
     * @param array  $matches
     *
     * @return mixed
     */
    public function run(string $method, array $matches)
    {
        $this->preCallAction($method);
        return call_user_func_array([$this, $method], $matches);
    }

    /**
     * Строим Url Path из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array  $variables Переменные для URL
     * @param array  $query Переменные для GET параметров
     *
     * @return string URL если запись найдена в route.yaml иначе пустая строка
     */
    public function getRoutePath($name, array $variables = [], array $query = []): string
    {
        if (!isset($this->init->getRouteData()[$name])) {
            return '#no-in-route';
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
     * Строим Полный путь из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array  $variables Переменные для URL
     * @param array  $query Данные для GET запроса
     *
     * @return string URL если запись найдена в route.yaml иначе пустая строка
     */
    public function getAbsoluteUrl($name, array $variables = [], $query = []): string
    {
        $request = new RequestHttp();
        $path = $this->getRoutePath($name, $variables, $query);

        return $request->getAbsolutePath($path);
    }

    /**
     * @param string $baseDir
     */
    public function setBaseDir(string $baseDir): void
    {
        $this->baseDir = $baseDir;
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
        return $this->baseDir . 'cache/';
    }

    /**
     * @param string $msg
     * @param int    $code
     *
     * @throws Error4xx
     */
    public function invokeError4xx(string $msg = '', int $code = Response::HTTP_STATUS_NOT_FOUND): void
    {
        throw new Error4xx('Error ' . $code . ' Msg: ' . $msg, $code);
    }

    /**
     * Редирект на другой URL
     *
     * @param string $url URL для редиректа
     * @param int    $code Код редиректа. see Response::REDIRECT_*
     *
     * @return Redirect;
     */
    public function redirectToUrl(string $url, int $code = Response::HTTP_STATUS_REDIRECT_PERMANENT): Redirect
    {
        header('Location: ' . $url, true, $code);

        return new Redirect();
    }

    /**
     * Строим URL из route.yaml
     *
     * @param string $name Название записи из route.yaml
     * @param array  $variables Переменные для URL
     * @param int    $code Код редиректа. see Response::REDIRECT_*
     *
     * @return Redirect
     */
    public function redirectToRoute(
        string $name,
        array $variables = [],
        int $code = Response::HTTP_STATUS_REDIRECT_PERMANENT
    ): Redirect
    {
        $url = $this->getRoutePath($name, $variables);
        header('Location: ' . $url, true, $code);

        return new Redirect();
    }

    public function initRender(\Twig_Environment $twig): void
    {
        // Получаем абсолютного пути
        $twig->addFunction(new \Twig_SimpleFunction('url', [$this, 'getAbsoluteUrl']));

        // Получение пути из route.yaml
        $twig->addFunction(new \Twig_SimpleFunction('route', [$this, 'getRoutePath']));
    }

    abstract public function preRender(
        \Twig_Environment $twig,
        \Twig_Loader_Filesystem $loader,
        string &$template,
        array &$variables
    ): void;

    abstract protected function preCallAction(string $method): void;
}
