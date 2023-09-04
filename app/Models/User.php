<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Stripe\Exception\ApiErrorException;

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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A user can initiate many transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's encrypted balance.
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            // Accepting null because initially user will not have a value
            get: fn (?string $value) => $value ? decrypt($value, true) : 0,
            set: fn (string $value) => encrypt($value),
        );
    }

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
            logger()->channel('single')->debug('New Stripe customer created!');
        });

        static::updated(queueable(function (User $customer) {
            if ($customer->hasStripeId())
                $customer->syncStripeCustomerDetails();
        }));

        static::retrieved(function (User $user) {
            if ($user->coins != (int) $user->balance)
                $user->update(['coins' => (int) $user->balance]);
        });

        //! User can be deleted in our application!
        static::deleting(function (User $user) {
            $user->transactions()->delete();

            if ($user->hasStripeId())
                try {
                    $user->stripe()->customers->delete($user->stripeId());
                    logger()->channel('single')->debug('Stripe customer deleted!');
                } catch (ApiErrorException $e) {
                    logger('stderr')->error('Could not delete stripe customer!');
                    logger()->channel('single')->error('Could not delete stripe customer!', ['message' => $e->getMessage()]);
                }
        });
    }
}
