<?php
declare(strict_types=1);

use Core\Http\Di\Di;
use Core\Http\FileUpload;
use Core\Http\Init;
use Core\Http\InitSettings;
use PHPUnit\Framework\TestCase;
use Core\Http\Di\Exception\DiException;

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
        $this->di->initDi('phpunit/asserts/di.yaml', $this->initSettings, Init::DEV_MODE_DEV);
    }

    /**
     * @covers \Core\Http\Di\Di::initDi
     * @covers \Core\Http\Di\Di::make
     * @covers \Core\Http\Di\Di::parseResRaw
     * @covers \Core\Http\Di\Di::parse
     * @covers \Core\Http\Di\Di::getRes
     */
    public function testFabricObject()
    {
        /** @var FileUpload $fileUpload */
        $fileUpload = $this->di->make('upload-file');
        $this->assertTrue($fileUpload instanceof FileUpload, 'Create FileUpload');

        $fileUpload = $this->di->make('upload-file');
        $this->assertTrue($fileUpload instanceof FileUpload, 'Create FileUpload');

        $test = $fileUpload->getName() === '_name_' && $fileUpload->getTmpName() === '_tmp_name_';
        $test = $test && $fileUpload->getSize() === 2018 && $fileUpload->getError() === 0;
        $test = $test && $fileUpload->getType() === 'php';
        $this->assertTrue($test, 'Bad array');

        $diHost = $this->di->make(Di::SITE_HOST);
        $this->assertEquals($diHost, $this->initSettings->getHttpHost(), 'Bad host');

        $diSiteRoot = $this->di->make(Di::SITE_ROOT);
        $this->assertEquals($diSiteRoot, $this->initSettings->getSiteRoot(), 'Bad site root');

        $this->di->getRes('upload-file');
        $array = $assertArray = [
            'name' => '_name_',
            'tmp_name' => '_tmp_name_',
            'size' => 2018,
            'error' => 0,
            'type' => 'php',
        ];
        $this->assertEquals($array, $assertArray, 'Bad array');

    }

    public function testException()
    {
        $exceptionCount = 0;

        try {
            $this->di->make('blabla');
        } catch (DiException $ex) {
            $exceptionCount++; // 1
            $this->assertEquals($ex->getCode(), DiException::CLASS_NAME_NOT_FOUND);
        }

        try {
            $this->di->make('badClass');
        } catch (DiException $ex) {
            $exceptionCount++; // 2
            $this->assertEquals($ex->getCode(), DiException::BAD_FORMAT);
        }

        try {
            $this->di->make('classWithoutClass');
        } catch (DiException $ex) {
            $exceptionCount++; // 3
            $this->assertEquals($ex->getCode(), DiException::CLASS_NOT_FOUND);
        }

        $this->assertEquals($exceptionCount, 3, 'Not all exception checked');
    }
}
