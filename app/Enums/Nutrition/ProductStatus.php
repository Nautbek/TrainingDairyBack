<?php

namespace App\Enums\Nutrition;

enum ProductStatus: int
{
    case Draft = 0;
    case Active = 1;
    case Decline = 2;
}
