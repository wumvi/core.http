<?php

use Core\Http\Http\Response\Json;
use Core\Http\Http\Response\StringRaw;
use Core\Http\Http\Response\Response;
use Core\Http\Http\Response\Html;
use Core\Http\Http\Response\FileHandle;
use Core\Http\Http\Exception\Error4xx;
use Core\Http\InitSettings;
use Core\Http\Init;
use \Core\Http\RequestHttp;

include realpath($_SERVER['SITE_ROOT']) . '/vendor/autoload.php';

$memoryUsage = 0;
$runMode = $_SERVER['RUN_MODE'] ?? Init::DEV_MODE_DEV;
if ($runMode) {
    $memoryUsage = memory_get_usage();
    ini_set('xdebug.show_error_trace', 0);
    ini_set('xdebug.show_exception_trace', 0);
}

$initSettings = new InitSettings([
    InitSettings::SITE_ROOT => realpath($_SERVER['SITE_ROOT']) . '/',
    InitSettings::DOCUMENT_URI => $_SERVER['DOCUMENT_URI'],
    InitSettings::HTTP_HOST => $_SERVER['HTTP_HOST'],
]);

$init = new Init($runMode, $initSettings);

try {
    $data = $init->initRoute($initSettings->getSiteRoot() . 'conf/route.yaml');
    if (!($data instanceof Response)) {
        throw new \Exception('data not instanceof Response');
    }

    http_response_code($data->getHttpStatus());
    if ($data->getCookies()) {
        array_map(function ($name, $value) {
            if ($value === null) {
                setCookie($name, '', $_SERVER['REQUEST_TIME'] - 3600);
            } else {
                call_user_func_array('setCookie', $value);
            }
        }, array_keys($data->getCookies()), $data->getCookies());
    }

    foreach($data->getHeaders() as $name => $value) {
        header($name. ': ' . $value);
    }

    if ($data instanceof Json) {
        echo $data->get();
        exit;
    }

    if ($data instanceof StringRaw) {
        echo $data->get();
        exit;
    }

    if ($data instanceof Html) {
        $html = $data->get();

        if (!isset($_COOKIE[session_name()])) {
            foreach ($data->getCssList() as $cssUrl) {
                header('link: <' . $cssUrl . '>; rel=preload; as=style', false);
            }

            foreach ($data->getJsList() as $jsUrl) {
                header('link: <' . $jsUrl . '>; rel=preload; as=script', false);
            }
        }

        echo $html;
        exit;
    }

    if ($data instanceof FileHandle) {
        $fr = $data->get();
        if (fpassthru($fr)) {
            fclose($fr);
        }

        exit;
    }
} catch (Error4xx $ex) {
    http_response_code($ex->getCode());
    if ($runMode === Init::DEV_MODE_DEV) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<pre>' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString() . '</pre>';
    }

    exit;
} catch (\Throwable $t) {
    if ($runMode === Init::DEV_MODE_DEV) {
        throw $t;
    }

    header("HTTP/1.1 500 Internal Server Error");
}
