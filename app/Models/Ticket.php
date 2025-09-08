<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'subject',
        'body',
        'status',
        'category',
        'explanation',
        'confidence',
        'note',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'confidence' => 'decimal:2',
    ];

    /**
     * Available ticket statuses.
     */
    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    /**
     * Boot function to generate ULID on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search tickets.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('body', 'like', "%{$search}%");
        });
    }

    /**
     * Check if ticket has been classified by AI.
     */
    public function hasBeenClassified(): bool
    {
        return !is_null($this->category);
    }

    /**
     * Check if ticket has internal notes.
     */
    public function hasNote(): bool
    {
        return !is_null($this->note) && trim($this->note) !== '';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}