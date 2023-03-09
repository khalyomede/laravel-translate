<?php

namespace Tests\Misc\App\Models;

use Illuminate\Database\Eloquent\Model;

final class BookType extends Model
{
    public const DRAMA = 1;
    public const FANTASTIC = 2;
    public const ADVENTURE = 3;

    public $timestamps = false;
}
