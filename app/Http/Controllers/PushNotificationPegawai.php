<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

/**
 * Push Notification Controller
 * 
 * @package App\Http\Controllers
 * @category Controller
 * @version 1.0.0
 * @since 1.0.0
 * 
 * @property \Kreait\Firebase\Factory $factory
 * @property \Kreait\Firebase\Messaging $messaging 
 * */
class PushNotificationPegawai extends Controller
{
    private $credentials = 'firebase_credentials.json';
    private $factory;
    protected $messaging;

    /**
     * Create a new controller instance.
     * 
     * @return void
     **/
    public function __construct()
    {
        // initialize firebase
        $this->factory = (new Factory)->withServiceAccount(base_path($this->credentials));

        // initialize messaging
        $this->messaging = $this->factory->createMessaging();
    }

    /**
     * Send push notification
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     **/
    public static function send(Request $request)
    {
        // check request requirements
        $check = self::checkRequest($request);

        if (!$check->getData()->success) {
            return $check;
        }


        // build notification message
        $message = self::buildNotification($check->getData()->data);

        // send notification
        self::sendNotification($message);

        // return response
        return response()->json([
            'success' => true,
            'message' => 'notification sent',
            'data'    => $message
        ], 200);
    }

    /**
     * Send push notification
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     **/
    
    public static function sendTo($title, $body, $data, $topics)
    {
        $check = self::checkRequest([
            'topic' => $topics,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if (!$check->getData()->success) {
            return $check;
        }

        // build notification message
        $message = self::buildNotification($check->getData()->data);

        // send notification
        self::sendNotification($message);

        // return response
        return response()->json([
            'success' => true,
            'message' => 'notification sent',
            'data'    => $message
        ], 200);
    }

    /**
     * Build notification message
     * 
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * 
     * @return \Kreait\Firebase\Messaging\CloudMessage
     * 
     * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#resource:-message
     **/
    private static function buildNotification($msg)
    {
        // build message
        $message = CloudMessage::withTarget('topic', $msg->topic)
            ->withNotification([
                'topic' => $msg->topic,
                'title' => $msg->title,
                'body'  => $msg->body
            ])->withData(json_decode(json_encode($msg->data), true));

        // return message
        return $message;
    }

    /**
     * Send notification
     * 
     * @param \Kreait\Firebase\Messaging\CloudMessage $message
     * 
     * @return void
     **/
    private static function sendNotification($message)
    {
        // send notification
        (new self)->messaging->send($message);
    }

    /**
     * Check request requirements
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @return \Illuminate\Http\Response
     **/
    private static function checkRequest($request)
    {
        // requirements data
        $requirements = ['topic', 'title', 'body', 'data'];
        $data         = [];

        // check request requirements
        foreach ($requirements as $requirement) {
            // if $request type of Illuminate\Http\Request
            if ($request instanceof Request) {
                if ($request->has($requirement)) {
    
                    if ($requirement == "data") {
                        // if data is not an array
                        if (!is_array($request->data)) {
                            $msg = "data bukan object";
                            return isFail($msg);
                        }
    
                        // if empty data
                        if (empty((array) $request->data)) {
                            $msg = "data tidak boleh kosong";
                            return isFail($msg);
                        }
    
                    }
    
                    $data[$requirement] = $request->$requirement;
                } else {
                    if ($requirement == 'data') {
                        $msg = $requirement . " is required and must be an object, at least add kategori to data, ex: {\"kategori\": \"ex\"}";
                        return isFail($msg);
                    }
                    return isFail($requirement . ' is required');
                }
            } else {
                // if $request type of array
                if (array_key_exists($requirement, $request)) {
    
                    if ($requirement == "data") {
                        // if data is not an array
                        if (!is_array($request['data'])) {
                            $msg = "data bukan object";
                            return isFail($msg);
                        }
    
                        // if empty data
                        if (empty((array) $request['data'])) {
                            $msg = "data tidak boleh kosong";
                            return isFail($msg);
                        }
    
                    }
    
                    $data[$requirement] = $request[$requirement];
                } else {
                    if ($requirement == 'data') {
                        $msg = $requirement . " is required and must be an object, at least add kategori to data, ex: {\"kategori\": \"ex\"}";
                        return isFail($msg);
                    }
                    return isFail($requirement . ' is required');
                }
    
            }

        }

        // return success response if requirements met
        return isSuccess($data, 'request requirements met');
    }
}