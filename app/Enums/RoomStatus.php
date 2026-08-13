<?php

namespace App\Enums;

enum RoomStatus: string
{

    case Concept = 'concept';
    case InReview = 'in_review';
    case Live = 'live';
    case Afgekeurd = 'afgekeurd';
    case Vakantie = 'vakantie';

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Concept => 'bg-prussian-blue/5 text-prussian-blue/60',
            self::InReview => 'bg-amber-500/10 text-amber-600',
            self::Live => 'bg-emerald-500/10 text-emerald-600',
            self::Afgekeurd => 'bg-ruby-red/10 text-ruby-red',
            self::Vakantie => 'bg-sky-500/10 text-sky-600',
        };
    }
}
