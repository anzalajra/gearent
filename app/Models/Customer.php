<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'nik',
        'address',
        'password',
        'email_verified_at',
        'is_verified',
        'verified_at',
        'verified_by',
        'customer_category_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'password' => 'hashed',
    ];

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function getCategoryDiscountPercentage(): float
    {
        return $this->category ? (float) $this->category->discount_percentage : 0.0;
    }

    public function getActiveRentals()
    {
        return $this->rentals()
            ->whereIn('status', [Rental::STATUS_PENDING, Rental::STATUS_ACTIVE, Rental::STATUS_LATE_PICKUP, Rental::STATUS_LATE_RETURN])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getPastRentals()
    {
        return $this->rentals()
            ->whereIn('status', [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get verification status
     * - not_verified: belum upload dokumen
     * - pending: sudah upload, menunggu verifikasi
     * - verified: sudah diverifikasi admin
     */
    public function getVerificationStatus(): string
    {
        if ($this->is_verified) {
            return 'verified';
        }

        $requiredTypes = DocumentType::getRequiredTypes();
        $uploadedDocs = $this->documents()->whereIn('document_type_id', $requiredTypes->pluck('id'))->get();

        if ($uploadedDocs->isEmpty()) {
            return 'not_verified';
        }

        // Check if all required docs are uploaded and at least pending
        $allUploaded = true;
        $allApproved = true;
        
        foreach ($requiredTypes as $type) {
            $doc = $uploadedDocs->where('document_type_id', $type->id)->first();
            if (!$doc) {
                $allUploaded = false;
                $allApproved = false;
            } elseif ($doc->status !== CustomerDocument::STATUS_APPROVED) {
                $allApproved = false;
            }
        }

        if (!$allUploaded) {
            return 'not_verified';
        }

        if ($allApproved) {
            return 'verified';
        }

        return 'pending';
    }

    public function getVerificationStatusLabel(): string
    {
        return match ($this->getVerificationStatus()) {
            'verified' => 'Terverifikasi',
            'pending' => 'Sedang Diverifikasi',
            'not_verified' => 'Belum Verifikasi',
        };
    }

    public function getVerificationStatusColor(): string
    {
        return match ($this->getVerificationStatus()) {
            'verified' => 'success',
            'pending' => 'warning',
            'not_verified' => 'danger',
        };
    }

    public function canRent(): bool
    {
        return $this->is_verified;
    }

    public function getMissingRequiredDocuments()
    {
        $requiredTypes = DocumentType::getRequiredTypes();
        $uploadedTypeIds = $this->documents()->pluck('document_type_id')->toArray();
        
        return $requiredTypes->filter(function ($type) use ($uploadedTypeIds) {
            return !in_array($type->id, $uploadedTypeIds);
        });
    }

    public function verify(int $userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $userId,
        ]);
    }

    public function unverify(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }
}