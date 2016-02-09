<?php
/**
 * Created by PhpStorm.
 * User: benfreke
 * Date: 25/01/16
 * Time: 07:50
 */

use Phalcon\Mvc\View;


class UsersController extends ControllerBase {

    public function getAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        $request = new \Phalcon\Http\Request();
        if ($request->isPost()){
            //The request is post, therefore it is receiving data
            //Data is in JSON format
            $data = json_decode(file_get_contents('php://input'), true);
            //print_r($data);
            $user = users::findFirst(array(
               "conditions" => 'id = :idVal: and apiKey = :keyVal:',
                'bind' => array('idVal' => $data['userid'], 'keyVal' => $data['user_api_key'])
            ));
            $recipient = users::findFirst(array(
                "conditions" => 'email = :emailVal:',
                'bind' => array('emailVal' => $data['email'])
            ));
            if ($user && $recipient){
                $data = array();
                $data['id'] = $recipient->id;
                $data['name'] = $recipient->name;
                echo (json_encode($data));
            }
        }
    }

    public function testGetAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        //API Url
        $url = 'http://localhost:8181/users/get';

        //Initiate cURL.
        $ch = curl_init($url);

        //The JSON data.
        $jsonData = array(
            'userid' => 1,
            'user_api_key' => '5UNI8bY960GN078yaEi4x0Xg3Fu113v4Jyx6491U715ky4p7054f0328r372636P',
            'email' => 'joe.bloggs@gmail.com'
        );

        //Encode the array into JSON.
        $jsonDataEncoded = json_encode($jsonData);

        //Tell cURL that we want to send a POST request.
        curl_setopt($ch, CURLOPT_POST, 1);

        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        //Execute the request
        echo $result = curl_exec($ch);

    }

}