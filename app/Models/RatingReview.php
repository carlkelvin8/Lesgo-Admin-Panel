<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingReview extends Model
{
    use HasFactory;

    protected $table = 'ratings_reviews';

    protected $fillable = [
        'user_id',
        'order_id',
        'driver_id',
        'service_id',
        'overall_rating',
        'service_rating',
        'driver_rating',
        'delivery_time_rating',
        'communication_rating',
        'professionalism_rating',
        'review_title',
        'review_comment',
        'review_tags',
        'review_images',
        'is_anonymous',
        'is_verified',
        'is_featured',
        'is_public',
        'status',
        'moderation_notes',
        'moderated_at',
        'moderated_by',
        'business_response',
        'business_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'review_tags' => 'array',
            'review_images' => 'array',
            'moderated_at' => 'datetime',
            'business_responded_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
