<?php
/**
 * Created by PhpStorm.
 * User: benfreke
 * Date: 25/01/16
 * Time: 07:50
 */

use Phalcon\Mvc\View;

/**
 * Class MessagesController
 * Messages controller provides functionality for messages in ChatLonger.
 */

class MessagesController extends ControllerBase {

    /**
     * The Send Action: This checks that the HTTP request is post, decodes the JSON expected and then checks
     * both the sending user's authentication (via their id and apiKey) and then checks the recipient exsits.
     * Should both of these conditions be true, the method will save the message in the database and return
     * with the message ID, Sender, Receiver, Content and Timestamp.
     */

    public function sendAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
        $request = new \Phalcon\Http\Request();
        if ($request->isPost()){
            $data = json_decode(file_get_contents('php://input'), true);
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
                    if ($recipient->regID == "NULL"){
                        $data['type'] = "PULL";
                    } else {
                        $this->sendToGCM($message, $recipient);
                        $data['type'] = "PUSH";
                    }
                    $array = $data;
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

    /**
     * Receive Action: receives a HTTP Post request, in JSON, checks to ensure the client is authenticated
     * and then responds with an array of JSON objects for each message that has not yet been downloaded from the
     * server
     */

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

    /**
     * Conversation Action: creates a new conversation between two users and returns the properties
     * of the new conversation
     */

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

    /**
     * Get User Action: receives a user's email address and responds with the user's full name
     */

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

    /**
     * Get User Action: receives a user's email address and responds with the user's full name
     */

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

    /**
     * @param messages $message
     * @param users $user
     *
     * Takes in the destination user and message and sends the relevant data to the Google Cloud Messaging service
     * for push notifications
     */

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