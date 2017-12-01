<?php
declare(strict_types=1);

use Assert\TestController;
use Core\Http\Exception\RouteException;
use Core\Http\Http\Exception\Error4xx;
use Core\Http\Http\Response\Json;
use Core\Http\Http\Response\StringRaw;
use Core\Http\Init;
use Core\Http\InitSettings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Core\Http\Init
 */
class InitTest extends TestCase
{
    private const ROUTE_FILE = 'phpunit/asserts/conf/route.yaml';

    /**
     * @covers \Core\Http\Init::initRoute
     * @covers \Core\Http\Init::makeController
     * @covers \Core\Http\Init::__construct
     */
    public function testExistAction(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/test/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check response');
        $this->assertTrue($response->get() === TestController::RESPONSE_TEST_ROUTE, 'Check response');
    }

    public function testIndexExist(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check response');
        $this->assertTrue($response->get() === TestController::RESPONSE_INDEX_ROUTE, 'Check response');
    }

    public function testNotFoundExist(): void
    {
        $this->expectException(Error4xx::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/blabla/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute('phpunit/asserts/conf/route.yaml');
    }

    /**
     * @covers \Core\Http\Init::initSafeAjaxRequest
     */
    public function testCheckAjax(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/ajax/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var Json $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof Json, 'Check response');
        // $this->assertTrue($response->get() === , 'Check response');

        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Проверяет на исключение, если стоит защита на ajax запросы
     */
    public function testCheckAjaxWrong(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectException(Error4xx::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/ajax/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute(self::ROUTE_FILE);

        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Проверяет статичной и локальной переменной
     */
    public function testLocalVar(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/vars/local/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check type');
        $this->assertTrue($response->get() === TestController::RESPONSE_LOCAL_VARIABLE, 'Check data');
    }

    /**
     * Проверяет глобальной переменной
     */
    public function testGlobalVar()
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/vars/global/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check type');
        $this->assertTrue($response->get() === TestController::RESPONSE_GLOBAL_VARIABLE, 'Check data');
    }

    /**
     * Проверяет регулятрное выражения в переменной
     */
    public function testRegexpVar(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/regexp/17081986/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check type');
        $this->assertTrue($response->get() === TestController::RESPONSE_REGEXP, 'Check data');
    }

    /**
     * Провеяет что кидает исключение если контроллера нет
     */
    public function testWrongController()
    {
        $this->expectException(RouteException::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/wrong-controller/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $init->initRoute(self::ROUTE_FILE);
    }

    /**
     * Провеяет исключение если метода нет
     */
    public function testWrongMethod(): void
    {
        $this->expectException('\Exception');

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/wrong-method/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $init->initRoute(self::ROUTE_FILE);
    }

    /**
     * Провеяет что кидает исключение если запись контроллера и метода кривое
     */
    public function testWrongControllerName(): void
    {
        $this->expectException(RouteException::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/wrong-method/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $init->initRoute('phpunit/asserts/conf/bad_route.yaml');
    }

    /**
     * Проверяет исключение, если переменной не найденно
     */
    public function testVarNotFound(): void
    {
        $this->expectException(Error4xx::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/var-not-found/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $init->initRoute(self::ROUTE_FILE);
    }

    /**
     * Проверяет запоминание переменной для контроллера
     */
    public function testMemoryVariable(): void
    {
        $data = 'saveme';

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/var_memory/' . $data . '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $response = $init->initRoute(self::ROUTE_FILE);

        $this->assertTrue($response instanceof StringRaw, 'Check type');
        $this->assertTrue($response->get() === $data, 'Check data');
    }

    /**
     * Проверяет исключение, если файла с роутами нет
     */
    public function testRouteFileNotExists(): void
    {
        $this->expectException(RouteException::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        /** @var StringRaw $response */
        $init->initRoute('phpunit/asserts/conf/blabla.yaml');
    }

    /**
     * @covers \Core\Http\Init::getSettings
     */
    public function testSettings(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $this->assertTrue($init->getSettings() === $initSettings, 'Check settings');
    }

    /**
     * @covers \Core\Http\Init::getRouteData
     */
    public function testRouteData(): void
    {
        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);

        $this->assertTrue($init->getRouteData() === [], 'Check before init');

        $init->initRoute('phpunit/asserts/conf/route-test.yaml');

        $data = [
            'vars' => ['global-vars' => '(global)',],
            'index' => ['controller' => '\\Assert\\TestController::indexAction', 'regexp' => null,],
        ];

        $this->assertEquals($init->getRouteData(), $data, 'Check before init');
    }

    /**
     * Тестирует конфиг с контроллером без метода
     */
    public function testControllerWithoutMethod(): void
    {
        $this->expectException(RouteException::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/without-method/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute(self::ROUTE_FILE);
    }

    /**
     * Тестирует контроллер с неверным интерфейсом
     */
    public function testControllerWithWrongInterface(): void
    {
        $this->expectException(RouteException::class);
        $this->expectExceptionCode(RouteException::BAD_INTERFACE);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/with-wrong-interface/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute(self::ROUTE_FILE);
    }

    /**
     * Тестирует пустой конфиг
     */
    public function testEmptyConfig(): void
    {
        $this->expectException(RouteException::class);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute('phpunit/asserts/conf/route-empty.yaml');
    }

    /**
     * Тестирует, если глобальной переменной нет
     */
    public function testGlobalVarNotFound(): void
    {
        $this->expectException(RouteException::class);
        $this->expectExceptionCode(RouteException::GLOBAL_VAR_NOT_FOUND);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => '/',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $init = new Init(Init::DEV_MODE_DEV, $initSettings);
        $init->initRoute('phpunit/asserts/conf/route_global_not_found.yaml');
    }
}
