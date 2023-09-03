<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUlid
{
    /**
     * The column name in which to store the UUID key.
     *
     * @var string
     */
    protected static string $ulid_key = 'ulid';

    /**
     * Laravel will call this function while booting the model.
     *
     * @return void
     */
    public static function bootHasUlid()
    {
        static::creating(function (Model $model): void {
            $model->{static::$ulid_key} = (string) Str::ulid();
        });
    }
}
