<?php

namespace App\Notifications;

use App\Models\ClassroomComment;
use App\Models\TrainingModule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClassroomCommentPosted extends Notification
{
    use Queueable;

    public function __construct(public ClassroomComment $comment) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $commentable = $this->comment->commentable;
        $author = $this->comment->author?->name ?? 'A classroom participant';
        $private = $this->comment->isPrivate();
        $type = $commentable instanceof TrainingModule ? 'module' : 'quiz';

        return [
            'title' => $private
                ? 'New private classroom comment'
                : $author.' added a class comment',
            'message' => $private
                ? 'Open the private conversation to read and reply.'
                : str($this->comment->body)->limit(140)->toString(),
            'url' => route('classroom-comments.index', [
                'type' => $type,
                'id' => $commentable?->getKey(),
            ]),
            'icon' => 'bell',
            'classroom_comment_id' => $this->comment->id,
            'visibility' => $this->comment->visibility,
        ];
    }
}
