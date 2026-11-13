<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'partner_id',
        'driver_id',
        'service_id',
        'pickup_address_id',
        'dropoff_address_id',
        'status',
        'scheduled_at',
        'accepted_at',
        'picked_up_at',
        'completed_at',
        'cancelled_at',
        'estimated_distance_m',
        'actual_distance_m',
        'estimated_fare',
        'actual_fare',
        'partner_share',
        'driver_share',
        'platform_fee',
        'payment_method',
        'payment_status',
        'cancel_reason',
        'meta',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'pickup_contact_name',
        'pickup_contact_phone',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'dropoff_contact_name',
        'dropoff_contact_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'accepted_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
            'fare_breakdown' => 'array',
            'proof_images' => 'array',
            'proof_uploaded_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function driver()
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function branch()
    {
        return $this->belongsTo(PartnerBranch::class, 'partner_branch_id');
    }

    public function proofImageUrls(): array
    {
        $images = $this->proof_images ?? [];
        if (! is_array($images)) {
            return [];
        }
        $urls = [];
        foreach ($images as $image) {
            $url = is_array($image) ? ($image['url'] ?? $image['image_url'] ?? $image['path'] ?? null) : $image;
            if (! is_string($url)) {
                continue;
            }
            if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true)) {
                $urls[] = $url;
            } elseif (preg_match('#^(?:/?storage/)?[a-zA-Z0-9_/-]+\.(?:png|jpe?g|webp|gif)$#i', $url) && ! str_contains($url, '..')) {
                $urls[] = asset('storage/'.preg_replace('#^/?storage/#', '', $url));
            }
        }

        return array_values(array_unique($urls));
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function trackingEvents()
    {
        return $this->hasMany(OrderTrackingEvent::class)->latest('event_time');
    }

    public function lesbuyItems()
    {
        return $this->hasMany(LesbuyItem::class);
    }
}
