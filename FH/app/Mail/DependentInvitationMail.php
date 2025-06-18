<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DependentInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $dependentType;
    public $referralNumber;

    /**
     * Create a new message instance.
     *
     * @param string $referralNumber
     * @param string $dependentType
     */
    public function __construct($referralNumber, $dependentType)
    {
        $this->dependentType = $dependentType;
        $this->referralNumber = $referralNumber;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.dependent-invitation')
                    ->subject('Invitation to Join as a Dependent')
                    ->with([
                        'dependentType' => $this->dependentType,
                        'referralNumber' => $this->referralNumber,
                    ]);
    }
}
