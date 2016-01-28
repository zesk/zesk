<?php
$user = $this->user;
/* @var $user User */

$article = $this->object;

/* @var $article Article */
$this->response->title($article->Title);

$byline = $article->Byline;

echo html::tag_open('div', ".article article-view $object->class_code_name()");
echo $this->theme('control/admin-edit');

echo html::tag("h1", $article->title);

echo html::tag_open('div', '.article-entry cmhtml');
echo $article->body;

echo html::tag_close('div');
echo html::tag_close('div');
