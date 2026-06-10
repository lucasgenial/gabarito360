<?php

namespace App\Enums;

enum AccessScope: string
{
    case GLOBAL = 'global';
    case EDUCATION_CENTER = 'nucleo';
    case SCHOOL = 'escola';
    case OPERATIONAL = 'operacional';
}
