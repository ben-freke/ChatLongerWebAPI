<?php


class messages extends Phalcon\Mvc\Model
{
    public $id;
    public $sender;
    public $receiver;
    public $content;
    public $timestamp;


    public function initialize()
    {
    }
    public function beforeSave()
    {
    }
    public function afterFetch()
    {
    }

}