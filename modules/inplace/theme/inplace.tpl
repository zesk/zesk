<?php
Module_Inplace::enabled(true);
/* @var $response zesk\Response_Text_HTML */
$response = $this->response;
$response->jquery("$('.inplace').inplace(" . json_encode($this->options) . ");");
