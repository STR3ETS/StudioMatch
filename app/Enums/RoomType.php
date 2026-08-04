<?php

namespace App\Enums;

enum RoomType: string
{
    // Fase 1: alleen opname en mix/master (BESLISSING 11).
    case Opname = 'opname';
    case MixMaster = 'mix_master';
}
