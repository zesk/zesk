<?php
namespace zesk;

/* @var $response zesk\Response */
$response = $this->response;
$response->jquery("$('.inplace').inplace(" . json_encode($this->options) . ");");
