<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Penawaran;
use App\Models\FollowUp;

class FollowUpNotification extends Notification
{
    use Queueable;

    public $penawaran;
    public $followUp;

    public function __construct(Penawaran $penawaran, FollowUp $followUp)
    {
        $this->penawaran = $penawaran;
        $this->followUp = $followUp;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Reminder Follow Up Penawaran',
            'body' => $this->followUp->nama . ' untuk penawaran ' . $this->penawaran->no_penawaran,
            'penawaran_id' => $this->penawaran->id_penawaran,
            'follow_up_id' => $this->followUp->id,
            'url' => url("/penawaran/{$this->penawaran->id_penawaran}/follow-up"),
        ];
    }
}