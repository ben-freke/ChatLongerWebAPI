<?php
/**
 * Created by PhpStorm.
 * User: benfreke
 * Date: 25/01/16
 * Time: 07:50
 */

use Phalcon\Mvc\View;


class UsersController extends ControllerBase {

    /**
     * Authenticate Action: used for user authentication when a user first logs in
     */

    public function authenticateAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        $request = new \Phalcon\Http\Request();
        if ($request->isPost()){
            //The request is post, therefore it is receiving data
            //Data is in JSON format
            $data = json_decode(file_get_contents('php://input'), true);
            //print_r($data);
            $user = users::findFirst(array(
               "conditions" => 'email = :emailVal: and password = :passwordVal:',
                'bind' => array('emailVal' => $data['email'], 'passwordVal' => $data['password'])
            ));

            if ($user){
                $data = array();
                $data['id'] = $user->id;
                $data['name'] = $user->name;
                $data['email'] = $user->email;
                $data['apikey'] = $user->apiKey;
                echo (json_encode($data));
            }
        }
    }

    /**
     * GCM ID Action: receives a user's GCM ID (which is sent to the phone by the GCM Service) for use
     * when sending messages
     */

    public function gcmidAction()
    {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        $request = new \Phalcon\Http\Request();
        if ($request->isPost()){
            $data = json_decode(file_get_contents('php://input'), true);
            $user = users::findFirst(array(
                "conditions" => 'id = :idVal: and apiKey = :apiVal:',
                'bind' => array('idVal' => $data['id'], 'apiVal' => $data['api'])
            ));
            if ($user){
                $user->regID = $data['regid'];
                if ($user->save()) echo json_encode(array("status" => "success"));
                else echo json_encode(array("status" => "failure", "cause" => "Error saving record"));
            }
            else echo json_encode(array("status" => "failure", "cause" => "Authentication Failure"));
        }
    }

}