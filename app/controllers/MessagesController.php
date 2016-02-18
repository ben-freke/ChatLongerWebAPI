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
                    $this->sendToGCM($message, $user);
                    echo (json_encode($array));

                }

                else {
                    echo $message->getMessages();
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

    public function conversationAction(){
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

            $conversation = new conversations();
            $conversation->user1 = $user->id;
            $conversation->user2 = $recipient->id;

            if ($user && $recipient && $conversation->save()){
                $data = array();
                $data['id'] = $conversation->id;
                $data['user1'] = $conversation->user1;
                $data['user2'] = $conversation->user2;
                $data['name'] = $recipient->name;
                echo (json_encode($data));
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

    public function testSendAction(){

        //API Url
        $url = 'http://comms.chatlonger.co.uk/messages/send';

        //Initiate cURL.
        $ch = curl_init($url);

        //The JSON data.
        $jsonData = array(
            'userid' => 4,
            'user_api_key' => "7Qut6Z77DeDF39T06z5730100G109TYHrsF8I134416xR4rE1PP34o776ueX7N5v",
            'recipient' => 2,
            'message' => "hello"
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
        $url = 'http://comms.chatlonger.co.uk/messages/receive';

        //Initiate cURL.
        $ch = curl_init($url);

        //The JSON data.
        $jsonData = array(
            'userid' => 4,
            'user_api_key' => '7Qut6Z77DeDF39T06z5730100G109TYHrsF8I134416xR4rE1PP34o776ueX7N5v',
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

    private function sendToGCM(messages $message, users $user)
    {
        define( 'API_ACCESS_KEY', self::GCM_API_KEY );
        $registrationIds = array($user->regID);
        $msg = array
        (
            'id'       => $message->id,
            'sender'       => $message->sender,
            'receiver'         => $message->receiver,
            'message'      => $message->content,
            'timestamp' => $message->timestamp
        );

        $fields = array
        (
            'registration_ids'  => $registrationIds,
            'data'              => $msg
        );

        $headers = array
        (
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        echo $result;
    }

}