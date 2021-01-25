<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Events\Dispatcher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotifSiaga extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $title;
    public $message;
    public $id;

    public function __construct($title,$message,$id)
    {
        $this->title = $title;
        $this->message = $message;
        $this->id = $id;
    }

    public function broadcastOn()
    {
        return ['saisiaga-channel-'.$this->id];
    }

    public function broadcastAs()
    {
        return 'saisiaga-event';
    }
}
