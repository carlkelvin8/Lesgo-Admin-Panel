<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVerification extends Model
{
    protected $fillable = [
        'user_id', 'verified_by', 'document_type', 'document_number',
        'document_urls', 'description', 'status', 'rejection_reason',
        'admin_notes', 'submitted_at', 'reviewed_at', 'expires_at',
        'metadata', 'verification_attempts', 'last_attempt_at',
    ];

    protected $casts = [
        'document_urls' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
