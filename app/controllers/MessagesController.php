<?php
/**
 * Created by PhpStorm.
 * User: benfreke
 * Date: 25/01/16
 * Time: 07:50
 */

use Phalcon\Mvc\View;


class MessagesController extends ControllerBase {

    public function sendAction(){
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
                "conditions" => 'id = :idVal:',
                'bind' => array('idVal' => $data['recipient'])
            ));
            if ($user && $recipient){
                $message = new messages();
                $message->sender = $user->id;
                $message->receiver = $recipient->id;
                $message->content = $data['message'];

                if ($message->save()){
                    $data = array();
                    $data['id'] = $message->id;
                    $data['sender'] = $message->sender;
                    $data['receiver'] = $message->receiver;
                    $data['content'] = $message->content;
                    $data['timestamp'] = $message->timestamp;
                    $array = $data;
                    echo (json_encode($array));
                }

                else {
                    echo  "error";
                }
                $messageDownload = new downloadedmessages();
                $messageDownload->messageID = $message->id;
                $messageDownload->userID = $user->id;
                $messageDownload->save();

            }
        }
    }

    public function receiveAction(){
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
            if ($user){
                $messages = messages::find(array(
                   "conditions" => "receiver = :idVal: or sender = :idVal:",
                    "bind" => array("idVal" => $user->id)
                ));



                $array = array();
                $i = 1;
                foreach ($messages as $message){
                    $downloaded = downloadedmessages::findFirst(array(
                        "conditions" => "userID = :idVal: and messageID = :msgVal:",
                        "bind" => array("idVal" => $user->id, "msgVal" => $message->id)
                    ));

                    //var_dump($downloaded);

                    if (!$downloaded){

                        //If the message has not been downloaded

                        $data = array();
                        $data['id'] = $message->id;
                        $data['sender'] = $message->sender;
                        $data['receiver'] = $message->receiver;
                        $data['content'] = $message->content;
                        $data['timestamp'] = $message->timestamp;
                        $array[$i] = $data;

                        $downloadedMsg = new downloadedmessages();
                        $downloadedMsg->messageID = $message->id;
                        $downloadedMsg->userID = $user->id;
                        $downloadedMsg->save();

                        $i++;

                    }

                }
                echo (json_encode($array));
            }
        }
    }

    public function getUserAction(){

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
            if ($user) {


                $user = users::findFirst(array(
                    "conditions" => "id = :idVal:",
                    "bind" => array("idVal" => $data['targetID'])
                ));

                $array['fullname'] = $user->firstName . " " . $user->lastName;

                echo (json_encode($array));
            }
        }

    }

    public function getconversationrecipientAction(){
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
            if ($user) {

                $conversation = conversations::findFirst(array(
                    "conditions" => 'id = :idVal',
                    "bind" => array("idVal" => $data['targetID'])
                ));

                if ($conversation->user1 == $user->id) $recipient = $conversation->user1;
                else $recipient = $conversation->user2;

                $user = users::findFirst(array(
                    "conditions" => "id = :idVal:",
                    "bind" => array("idVal" => $recipient)
                ));

                $array['fullname'] = $user->firstName . " " . $user->lastName;

                echo (json_encode($array));
            }
        }

    }

    public function testSendAction($sender, $receiver, $message, $apKey){

        //API Url
        $url = 'http://comms.chatlonger.co.uk/messages/send';

        //Initiate cURL.
        $ch = curl_init($url);

        //The JSON data.
        $jsonData = array(
            'userid' => $sender,
            'user_api_key' => $apKey,
            'recipient' => $receiver,
            'message' => $message
        );

        //Encode the array into JSON.
        $jsonDataEncoded = json_encode($jsonData);

        //Tell cURL that we want to send a POST request.
        curl_setopt($ch, CURLOPT_POST, 1);

        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        //Execute the request
        if ($result != null){
            echo $result;
        } else {
            echo "error";
        }


    }

    public function testReceiveAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        //API Url
        $url = 'http://comms.chatlonger.co.uk/messages/send';

        //Initiate cURL.
        $ch = curl_init($url);

        //The JSON data.
        $jsonData = array(
            'userid' => 2,
            'user_api_key' => 'VzMk8S89UfBDJnqYJFxxtVGIH7FZVin4ZOq4MZcz2qcPsaLYv865cKuA67HuRa4b',
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
        $result = curl_exec($ch);

    }

}