<?php
declare(strict_types=1);

namespace Core\Http\Http\Response;

use Core\Http\Init;
use Core\Http\RootController;

/**
 * HTML Response
 */
class Html extends Response
{
    const CONTENT_TYPE = 'text/html; charset=utf-8';

    /**
     * @var string Имя файла шаблона
     */
    protected $template = '';

    /**
     * @var array Переменные для шаблона
     */
    protected $params = [];

    /**
     * @var RootController Контроллер
     */
    protected $controller;

    /**
     * @var \Twig_Environment Twig
     */
    protected $twig;

    /**
     * @var \Twig_Loader_Filesystem Loader для twig
     */
    protected $loader;

    /**
     * @var array Массив CSS стилей для подключения
     */
    private $cssList = [];

    /**
     * @var array Массив JS скрптов для подключения
     */
    private $jsList = [];

    /**
     * Сonstructor.
     *
     * @param string $template Имя файла шаблона
     * @param array $params Переменные для шаблона
     * @param RootController $controller Контроллер
     * @param int $httpStatus Статус ответа
     */
    public function __construct(
        string $template,
        array $params,
        RootController $controller,
        int $httpStatus = self::HTTP_CODE_OK
    ) {
        $this->template = $template;
        $this->params = $params;
        $this->controller = $controller;
        $this->httpStatus = $httpStatus;

        $params = [];
        if ($controller->getRunMode() === Init::DEV_MODE_DEV) {
            $params['strict_variables'] = true;
        } else {
            $params['cache'] = $this->controller->getCacheDir();
        }

        $this->loader = new \Twig_Loader_Filesystem($this->controller->getTplDir());
        $this->twig = new \Twig_Environment($this->loader, $params);

        $this->twig->addFunction(new \Twig_SimpleFunction('assetJs', [$this, 'assetJs']));
        $this->twig->addFunction(new \Twig_SimpleFunction('assetCss', [$this, 'assetCss']));
        $this->twig->addFunction(new \Twig_SimpleFunction('assetExtJs', [$this, 'assetExtJs']));
        $this->twig->addFunction(new \Twig_SimpleFunction('assetExtCss', [$this, 'assetExtCss']));

        $this->controller->initRender($this->twig);

        $this->addHeader('Content-Type', $this->getContentType());
    }

    /**
     * Возвращает отрендеренную HTML страницу
     *
     * @return string HTML код
     */
    public function get(): string
    {
        $this->controller->preRender($this->twig, $this->loader, $this->template, $this->params);
        $template = $this->twig->load($this->template);

        return $template->render($this->params);
    }

    /**
     * Возвращает Twig
     *
     * @return \Twig_Environment Twig
     */
    public function getTwig(): \Twig_Environment
    {
        return $this->twig;
    }

    /**
     * Возвращает HTML код для подключение JS скрипта
     *
     * @param string $assetName Название скрипта
     * @param bool $isPreload Использовать ли preload header
     *
     * @return string HTML код
     */
    public function assetJs(string $assetName, $isPreload = false): string
    {
        $url = sprintf('/res/js/%s.js?%s', $assetName, $this->controller->getBuildInfo());
        if ($isPreload) {
            $this->jsList[] = $url;
        }

        return vsprintf('<script src="%s"></script>', [$url,]);
    }

    /**
     * Возвращает HTML код для подключение CSS стиля
     *
     * @param string $assetName Название стиля
     * @param bool $isPreload Использовать ли preload header
     *
     * @return string HTML код
     */
    public function assetCss(string $assetName, $isPreload = false): string
    {
        $url = vsprintf('/res/css/%s.css?%s', [$assetName, $this->controller->getBuildInfo(),]);
        if ($isPreload) {
            $this->cssList[] = $url;
        }

        return vsprintf('<link rel="stylesheet" href="%s"/>', [$url,]);
    }

    /**
     * Возвращает HTML код для подключение внешнего CSS стиля
     *
     * @param string $url URL стиля
     * @param bool $isPreload Использовать ли preload header
     *
     * @return string HTML код
     */
    public function assetExtCss(string $url, $isPreload = false): string
    {
        if ($isPreload) {
            $this->cssList[] = $url;
        }

        return vsprintf('<link rel="stylesheet" href="%s?%s"/>', [$url,$this->controller->getBuildInfo(),]);
    }

    /**
     * Возвращает HTML код для подключение внешнего JS скрипта
     *
     * @param string $url URL скрипта
     * @param bool $isPreload Использовать ли preload header
     *
     * @return string HTML код
     */
    public function assetExtJs(string $url, $isPreload = false): string
    {
        if ($isPreload) {
            $this->jsList[] = $url;
        }

        return vsprintf('<script src="%s?%s"></script>', [$url,$this->controller->getBuildInfo(),]);
    }

    /**
     * Возвращает список CSS стилей для подключения
     *
     * @return array Список стилей
     */
    public function getCssList(): array
    {
        return $this->cssList;
    }

    /**
     * Возвращает список JS скриптов для подключения
     *
     * @return array Список скриптов
     */
    public function getJsList(): array
    {
        return $this->jsList;
    }

    public function getContentType(): string
    {
        return self::CONTENT_TYPE;
    }
}
