<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use function Illuminate\Events\queueable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'coins',
        'balance',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'balance',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'encrypted',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->balance = $user->balance ?: 0;
        });

        static::created(function (User $user) {
            $user->createAsStripeCustomer([
                'description' => sprintf('Created by %s on registration', config('app.name')),
            ]);
        });

        static::updated(queueable(function (User $customer) {
            if ($customer->hasStripeId())
                $customer->syncStripeCustomerDetails();
        }));

        static::retrieved(function (User $user) {
            if ($user->coins != (int) $user->balance)
                $user->update(['coins' => (int) $user->balance]);
        });
    }
}
