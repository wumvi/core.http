<?php

namespace Core\Http;

/**
 * Interface MinimalControllerInterface
 *
 * @codeCoverageIgnore
 */
interface MinimalControllerInterface
{
    /**
     * @param string $method
     * @param array  $matches
     *
     * @return mixed
     */
    public function run(string $method, array $matches);
    public function setBaseDir(string $path): void;
}
