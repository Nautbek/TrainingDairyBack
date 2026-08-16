<?php

namespace Modules\TrainingDiary\Enums;

enum FeedbackStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::InProgress => 'В работе',
            self::Answered => 'Отвечен',
            self::Closed => 'Закрыт',
        };
    }
}
