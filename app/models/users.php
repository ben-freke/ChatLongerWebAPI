<?php


class users extends Phalcon\Mvc\Model
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $apiKey;
    public $regID;



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