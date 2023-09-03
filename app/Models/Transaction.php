<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'amount',
        'received',
        'session',
        'secret',
        'status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'received' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * A transaction is bound to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope the query to include pending transactions.
     */
    public function scopePending(Builder $query)
    {
        $query->where('status', 'pending');
    }

    /**
     * Scope the query to include pending transactions.
     */
    public function scopeUnpaid(Builder $query)
    {
        $query->where('status', 'pending');
    }

    /**
     * Scope the query to include pending transactions.
     */
    public function scopeSuccessful(Builder $query)
    {
        $query->where('status', 'paid');
    }

    /**
     * Scope the query to include pending transactions.
     */
    public function scopePaid(Builder $query)
    {
        $query->where('status', 'paid');
    }
}
