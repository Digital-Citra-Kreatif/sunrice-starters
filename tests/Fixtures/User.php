<?php

declare(strict_types=1);

namespace Sunrice\Starters\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/** The test app's user: the panel needs HasRoles. */
final class User extends Authenticatable
{
    use HasRoles;

    protected $table = 'users';

    protected $guarded = [];
}
