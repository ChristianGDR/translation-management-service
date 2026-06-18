<?php

namespace App\Enums;

enum TranslationContext: string
{
    case Mobile = 'mobile';
    case Desktop = 'desktop';
    case Web = 'web';
}
