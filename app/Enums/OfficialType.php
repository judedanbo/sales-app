<?php

namespace App\Enums;

enum OfficialType: string
{
    case PRINCIPAL = 'principal';
    case VICE_PRINCIPAL = 'vice_principal';
    case ADMIN = 'admin';
    case ACCOUNTANT = 'accountant';
    case COORDINATOR = 'coordinator';
}