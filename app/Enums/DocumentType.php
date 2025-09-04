<?php

namespace App\Enums;

enum DocumentType: string
{
    case REGISTRATION = 'registration';
    case AFFILIATION = 'affiliation';
    case TAX_CERTIFICATE = 'tax_certificate';
    case LICENSE = 'license';
}