<?php

namespace App\Enums;

enum ContactType: string
{
    case MAIN = 'main';
    case ADMISSION = 'admission';
    case ACCOUNTS = 'accounts';
    case SUPPORT = 'support';
}