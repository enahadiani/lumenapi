<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Events\Dispatcher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotifApv extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $id;

    public function __construct($message,$id)
    {
        $this->message = $message;
        $this->id = $id;
    }

    public function broadcastOn()
    {
        return ['saiapv-channel-'.$this->id];
    }

    public function broadcastAs()
    {
        return 'saiapv-event';
    }
}
