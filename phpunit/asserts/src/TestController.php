<?php
declare(strict_types=1);

namespace Assert;

use Core\Http\Http\Response\Json;
use Core\Http\Http\Response\StringRaw;
use Core\Http\RootController;

class TestController extends RootController
{
    public const RESPONSE_INDEX_ROUTE = 'index-route-response';
    public const RESPONSE_TEST_ROUTE = 'test-route-response';
    public const RESPONSE_AJAX_ROUTE = ['ajax-response',];
    public const RESPONSE_LOCAL_VARIABLE = 'local-response';
    public const RESPONSE_GLOBAL_VARIABLE = 'global-response';
    public const RESPONSE_REGEXP = 'regexp-response';


    public function indexAction(): StringRaw
    {
        return new StringRaw(self::RESPONSE_INDEX_ROUTE);
    }

    public function testAction(): StringRaw
    {
        return new StringRaw(self::RESPONSE_TEST_ROUTE);
    }

    public function ajaxAction(): Json
    {
        return new Json(self::RESPONSE_AJAX_ROUTE);
    }

    public function localVarAction(): StringRaw
    {
        return new StringRaw(self::RESPONSE_LOCAL_VARIABLE);
    }

    public function globalVarAction(): StringRaw
    {
        return new StringRaw(self::RESPONSE_GLOBAL_VARIABLE);
    }

    public function regexpAction(): StringRaw
    {
        return new StringRaw(self::RESPONSE_REGEXP);
    }

    public function memoryVarAction(string $path, string $data): StringRaw
    {
        return new StringRaw($data);
    }

    public function preRender(
        \Twig_Environment $twig,
        \Twig_Loader_Filesystem $loader,
        string &$template,
        array &$variables
    ): void {

    }

    protected function preCallAction(string $method): void
    {

    }
}
