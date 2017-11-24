<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Core\Http\Init;
use Core\Http\InitSettings;

/**
 * @covers \Core\Http\Init
 */
class InitTest extends TestCase
{
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
    }

    /**
     * @covers \Core\Http\FileUpload::getName
     * @covers \Core\Http\FileUpload::getTmpName
     * @covers \Core\Http\FileUpload::getSize
     * @covers \Core\Http\FileUpload::getError
     * @covers \Core\Http\FileUpload::getType
     */
    public function testModel(): void
    {
        $init = new Init(Init::DEV_MODE_DEV, $this->initSettings);
        $init->initRoute('phpunit/asserts/route.yaml');

        //$init->makeController('')

        $this->assertTrue('', 'Bad array');
    }
}
