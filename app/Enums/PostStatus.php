<?php

namespace App\Enums;

enum PostStatus: string
{

    case Concept = 'concept';
    case Gepubliceerd = 'gepubliceerd';

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Concept => 'bg-prussian-blue/5 text-prussian-blue/60',
            self::Gepubliceerd => 'bg-emerald-500/10 text-emerald-600',
        };
    }

    public function label(): string
    {
        return __('admin.blog.status_' . $this->value);
    }
}
