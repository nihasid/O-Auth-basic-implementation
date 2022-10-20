<?php

namespace App\Helpers;
/* *created by Niha Siddiqui 2022-10-19
    * all file uploads defined in this file
*/

use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;

class GlobalFunction
{
    static function sendEmail($emailType, $title = '', $emailData = [], $recieverEmail, $senderEmail = 'no-reply@fasi.app')
    {

        try {

            $appEnv = \Illuminate\Support\Facades\App::environment();
            $subject = Constant::EMAIL_SUBJECT[$emailType];
            $mailInfo = [
                'title' => (isset($title) && !empty($title)) ? $title : Constant::EMAIL_SUBJECT[$emailType],
                'email_message' => (isset($emailData) && array_key_exists('email_message', $emailData)) ? $emailData['email_message'] : ''
            ];

            if (count($emailData) > 0 && array_key_exists('url', $emailData)) {
                $emailInfo['url'] = $emailData['url'];
            }

            $is_sent = Mail::to($recieverEmail)->send(new WelcomeMail($mailInfo, $senderEmail, $subject));

            return Response::HTTP_OK;
        } catch (\Exception $e) {
            Log::error('sendMail-helperFunction ' . $e);
            return $e;
        }
    }
}
