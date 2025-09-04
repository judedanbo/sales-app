<?php

namespace App\Enums;

enum OwnershipType: string
{
    case INDIVIDUAL = 'individual';
    case GROUP = 'group';
    case TRUST = 'trust';
    case GOVERNMENT = 'government';
    case SOCIETY = 'society';
}