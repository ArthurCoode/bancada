<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Technician = 'technician';
    case Attendant = 'attendant';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dono',
            self::Technician => 'Técnico',
            self::Attendant => 'Atendente',
        };
    }
}