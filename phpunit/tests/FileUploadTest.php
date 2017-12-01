<?php
declare(strict_types=1);

use Core\Http\FileUpload;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Core\Http\FileUpload
 */
class FileUploadTest extends TestCase
{
    /**
     * @covers \Core\Http\FileUpload::getName
     * @covers \Core\Http\FileUpload::getTmpName
     * @covers \Core\Http\FileUpload::getSize
     * @covers \Core\Http\FileUpload::getError
     * @covers \Core\Http\FileUpload::getType
     */
    public function testModel(): void
    {
        $array = $assertArray = [
            'name' => '_name_',
            'tmp_name' => '_tmp_name_',
            'size' => 2018,
            'error' => 0,
            'type' => 'php',
        ];
        $fileUpload = new FileUpload($array);
        $test = $fileUpload->getName() === '_name_' && $fileUpload->getTmpName() === '_tmp_name_';
        $test = $test && $fileUpload->getSize() === 2018 && $fileUpload->getError() === 0;
        $test = $test && $fileUpload->getType() === 'php';
        $this->assertTrue($test, 'Bad array');
    }
}
