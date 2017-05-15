<?php
namespace zesk;

/* @var $response zesk\Response_Text_HTML */
$response = $this->response;
$response->jquery("$('.inplace').inplace(" . json_encode($this->options) . ");");
