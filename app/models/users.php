<?php


class users extends Phalcon\Mvc\Model
{
    public $id;
    public $firstName;
    public $lastName;
    public $emailAddress;
    public $password;
    public $apiKey;



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