<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BloommonieAppResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('app.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Your Bloommonie App Password')
            ->greeting('Hello!')
            ->line('We received a request to reset your Bloommonie account password.')
            ->line('Click the button below to open Bloommonie and create a new password.')
            ->action('Reset My Password', $url)
            ->line('This password reset link will expire according to our security settings.')
            ->line('If you did not request a password reset, you can safely ignore this email.')
            ->salutation('Regards, Bloommonie');
    }
}