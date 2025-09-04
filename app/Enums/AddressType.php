<?php

namespace App\Enums;

enum AddressType: string
{
    case PHYSICAL = 'physical';
    case MAILING = 'mailing';
    case BILLING = 'billing';
}