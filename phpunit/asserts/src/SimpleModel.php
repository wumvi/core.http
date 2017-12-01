<?php
declare(strict_types=1);

namespace Assert;

class SimpleModel
{
    /**
     * @var int
     */
    private $number;

    public function __construct(int $number)
    {
        $this->number  = $number;
    }

    public function getNumber() : int {
        return  $this->number;
    }
}
