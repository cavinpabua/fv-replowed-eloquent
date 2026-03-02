<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $message;
    public $username;
    public $profilePicture;
    public $createdAt;

    /**
     * Create a new event instance.
     */
    public function __construct($messageId, $message, $username, $profilePicture, $createdAt)
    {
        $this->messageId = $messageId;
        $this->message = $message;
        $this->username = $username;
        $this->profilePicture = $profilePicture;
        $this->createdAt = $createdAt;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('global-chat'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'messageId' => $this->messageId,
            'message' => $this->message,
            'username' => $this->username,
            'profilePicture' => $this->profilePicture,
            'createdAt' => $this->createdAt,
        ];
    }
}
