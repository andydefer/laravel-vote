<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Enums;

enum VoteType: string
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';
    case ABSTENTION = 'abstention';
    case NEUTRAL = 'neutral';

    public function getLabel(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positif',
            self::NEGATIVE => 'Négatif',
            self::ABSTENTION => 'Abstention',
            self::NEUTRAL => 'Neutre',
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::POSITIVE => '👍',
            self::NEGATIVE => '👎',
            self::ABSTENTION => '🤷',
            self::NEUTRAL => '😐',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::POSITIVE => 'green',
            self::NEGATIVE => 'red',
            self::ABSTENTION => 'gray',
            self::NEUTRAL => 'blue',
        };
    }
}