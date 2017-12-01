<?php
declare(strict_types=1);

namespace Assert;

use Core\Http\FileUpload;

class TestModel
{
    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var int
     */
    private $number;

    public function __construct(FileUpload $fileUpload, int $number)
    {
        $this->fileUpload = $fileUpload;
        $this->number  = $number;
    }

    public function getFileUpload(): FileUpload
    {
        return $this->fileUpload;
    }

    public function getNumber() : int {
        return  $this->number;
    }
}
