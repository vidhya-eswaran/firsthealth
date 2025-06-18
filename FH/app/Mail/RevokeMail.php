<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RevokeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $content;

    /**
     * Create a new message instance.
     *
     * @param string $content
     * @param string $title
     */
    public function __construct(array $content, string $title)
    {
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.dependent-revoke')
                    ->subject('Invitation Revoked')
                   ->with([
                        'user_name' => $this->content['user_name'],
                        'membership_name' => $this->content['membership_name'],
                        'message' => $this->content['message'],
                    ]);
    }
}
