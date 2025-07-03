<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Message;

class TeamMentionNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->name ?? null,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'file_path' => $this->message->file_path,
            'created_at' => $this->message->created_at,
            'group_id' => $this->message->conversation->group_id,
            'group_name' => $this->message->conversation->group->name ?? null,
            'is_team_mention' => true,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
