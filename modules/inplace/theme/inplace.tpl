<?php declare(strict_types=1);
namespace zesk;

/* @var $response zesk\Response */
$response = $this->response;
$response->jquery("$('.inplace').inplace(" . json_encode($this->options) . ");");
