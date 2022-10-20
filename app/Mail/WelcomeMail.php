<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Constant;
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;
    public $mailInfo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailInfo, $senderAddress = '', $subject = '')
    {
        //
        $this->mailInfo = $mailInfo;
        $this->senderAddress = (!empty($senderAddress) ? $senderAddress : Constant::EMAIL_SENDER['notification']);
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $senderEmailAddress = Constant::EMAIL_SENDER[$this->senderAddress];
        return $this->from($senderEmailAddress)->subject($this->subject)->markdown('Email.welcomeMail')
                ->with('mailInfo', $this->mailInfo);
        // return $this->markdown('Email.welcomeMail');
    }
}
