<?php
declare(strict_types=1);

use Assert\TestModel;
use Core\Http\Di\Di;
use Core\Http\Di\Exception\DiException;
use Core\Http\FileUpload;
use Core\Http\Init;
use Core\Http\InitSettings;
use PHPUnit\Framework\TestCase;
use Assert\SimpleModel;

/**
 * @covers \Core\Http\Di\Di
 */
class DiTest extends TestCase
{
    /**
     * @var Di
     */
    private $di;

    /**
     * @var InitSettings
     */
    private $initSettings;

    public function setUp()
    {
        $this->initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => 'uri',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $this->di = new Di();
        $this->di->initDi(
            'phpunit/asserts/conf/di.yaml',
            $this->initSettings,
            Init::DEV_MODE_DEV
        );
    }

    /**
     * @covers \Core\Http\Di\Di::initDi
     * @covers \Core\Http\Di\Di::make
     * @covers \Core\Http\Di\Di::parseResRaw
     * @covers \Core\Http\Di\Di::parse
     * @covers \Core\Http\Di\Di::getRes
     */
    public function testCommon(): void
    {
        /** @var FileUpload $fileUpload */
        $fileUpload = $this->di->make('upload-file');
        $this->assertTrue($fileUpload instanceof FileUpload, 'Create FileUpload');

        $test = $fileUpload->getName() === '_name_' && $fileUpload->getTmpName() === '_tmp_name_';
        $test = $test && $fileUpload->getSize() === 2018 && $fileUpload->getError() === 0;
        $test = $test && $fileUpload->getType() === 'php';
        $this->assertTrue($test, 'Bad array');

        $diHost = $this->di->make(Di::SITE_HOST);
        $this->assertEquals($diHost, $this->initSettings->getHttpHost(), 'Get host');

        $diSiteRoot = $this->di->make(Di::SITE_ROOT);
        $this->assertEquals($diSiteRoot, $this->initSettings->getSiteRoot(), 'Get site root');

        $runMode = $this->di->make(Di::RUN_MODE);
        $this->assertEquals($runMode, Init::DEV_MODE_DEV, 'Get run mode');

        $number = $this->di->getRes('get-number');
        $this->assertTrue($number === 0, 'Get number');

        $this->di->getRes('upload-file');
        $array = $assertArray = [
            'name' => '_name_',
            'tmp_name' => '_tmp_name_',
            'size' => 2018,
            'error' => 0,
            'type' => 'php',
        ];
        $this->assertEquals($array, $assertArray, 'Get Res');

    }

    /**
     * Тестирует получение класса, которого нет
     */
    public function testClassNotFound()
    {
        $this->expectException('\Core\Http\Di\Exception\DiException');
        $this->expectExceptionCode(DiException::CLASS_NAME_NOT_FOUND);
        $this->di->make('blabla');
    }

    /**
     * Тестирует получение кривого класса
     */
    public function testBadClass(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::BAD_FORMAT);
        $this->di->make('badClass');
    }

    /**
     * Тестирует получение класса, без заполненего поля-класс
     */
    public function testClassWithOutClass(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::CLASS_NOT_FOUND);
        $this->di->make('classWithoutClass');
    }

    /**
     * Тестирует директиву include
     */
    public function testIncludeDi(): void
    {
        /** @var InitSettings $initSettings */
        $initSettings = $this->di->make('base-service');
        $this->assertTrue($initSettings instanceof InitSettings, 'Check include');
    }

    /**
     * Тестирует получение ресурса, если его нет
     */
    public function testResNotFound(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::RES_DATA_NOT_FOUND);
        $this->di->getRes('blabla');
    }

    /**
     * Тестирует создание Di, когда конфиг указан не верно
     */
    public function testDiConfigNotFound(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::FILE_NOT_FOUND);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => 'uri',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $di = new Di();
        $di->initDi(
            'phpunit/asserts/conf/blabla.yaml',
            $initSettings,
            Init::DEV_MODE_DEV
        );
    }

    /**
     * Тестирует создание Di, но директива include подключает файл котого нет
     */
    public function testDiIncludeNotFoundFile(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::FILE_NOT_FOUND);

        $initSettings = new InitSettings([
            InitSettings::SITE_ROOT => getcwd() . DIRECTORY_SEPARATOR,
            InitSettings::DOCUMENT_URI => 'uri',
            InitSettings::HTTP_HOST => 'localhost',
        ]);

        $di = new Di();
        $di->initDi(
            'phpunit/asserts/conf/di_bad.yaml',
            $initSettings,
            Init::DEV_MODE_DEV
        );
    }

    /**
     * Тестирует получение raw из файла подключенного с помощью include
     */
    public function testGetRawInclude(): void
    {
        $data = $this->di->getRes('include-raw');
        $this->assertTrue($data === 'getme', 'Get include raw');
    }

    /**
     * Тестирует вложенные классы
     */
    public function testMultiClass(): void
    {
        /** @var TestModel $testModel */
        $testModel = $this->di->make('test-model');
        $this->assertTrue($testModel instanceof TestModel, 'Test instanceof');
        $this->assertTrue($testModel->getFileUpload() instanceof FileUpload, 'Test instanceof');
        $this->assertTrue($testModel->getFileUpload()->getName() === '_name_', 'Get name');
        $this->assertTrue($testModel->getNumber() === 5, 'Get number');
    }

    /**
     * Тестирует создание класса, если ресурс в классе не найден
     */
    public function testResNotFoundInClass(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionCode(DiException::RES_DATA_NOT_FOUND);

        $this->di->make('bad-upload-file');
    }

    /**
     * Тестирует кеширование объектов
     */
    public function testCache(): void
    {
        $this->assertTrue(
            $this->di->make('test-model') === $this->di->make('test-model'),
            'Test cache'
        );
    }

    /**
     * Тестирует примитивные типы в аргументах класса
     */
    public function testTestRawInClass(): void
    {
        /** @var SimpleModel $testModel */
        $simpleModel = $this->di->make('simple-model');
        $this->assertTrue($simpleModel instanceof SimpleModel, 'Check instanceof');
        $this->assertTrue($simpleModel->getNumber() === 6, 'Get number');
    }
}
