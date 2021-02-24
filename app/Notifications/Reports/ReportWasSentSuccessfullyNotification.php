<?php

namespace App\Notifications\Reports;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportWasSentSuccessfullyNotification extends Notification
{
    use Queueable;

    private string $url;
    private User $user;

    /**
     * Create a new notification instance.
     *
     * @param string $url
     * @param User $user
     */
    public function __construct(string $url, User $user)
    {

        $this->url = $url;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('contact@company.com', 'Company name')
            ->greeting(sprintf("Hi, %s!", $this->user->full_name))
            ->line("We have ended exporting the report, which you requested.")
            ->action('You can see it by click it', $this->url)
            ->line('Thank you for using Cattr!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return get_object_vars($this);
    }
}
