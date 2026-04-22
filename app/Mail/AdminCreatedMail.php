<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $plainPassword;

    public function __construct($admin, $plainPassword)
    {
        $this->admin = $admin;
        $this->plainPassword = $plainPassword;
    }

    public function build()
    {
        return $this->subject('Your Inventory Account Credentials')
                    ->view('emails.admin-created');
    }
}