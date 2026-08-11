<?php

namespace Modules\Nutrition\Enums;

enum ProductStatus: int
{
    case Draft = 0;
    case Active = 1;
    case Decline = 2;
}
