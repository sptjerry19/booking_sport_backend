<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'email',
        'amenities',
        'images',
        'opening_time',
        'closing_time',
        'status',
    ];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = ['image_urls'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        return $query->selectRaw(
            '*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
            [$latitude, $longitude, $latitude]
        )->having('distance', '<', $radiusKm)->orderBy('distance');
    }

    /**
     * Get image URLs accessor
     */
    public function getImageUrlsAttribute(): array
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }

        $disk = config('filesystems.default', 'public');

        return array_map(function ($imagePath) use ($disk) {
            // Nếu đã là URL đầy đủ (http/https), trả về nguyên
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                return $imagePath;
            }

            // Nếu là path trong storage, tạo URL đầy đủ
            if ($disk === 's3') {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                $storage = Storage::disk($disk);
                return $storage->url($imagePath);
            } else {
                return Storage::url($imagePath);
            }
        }, $this->images);
    }

    /**
     * Get single image URL (first image)
     */
    public function getFirstImageUrlAttribute(): ?string
    {
        $imageUrls = $this->getImageUrlsAttribute();
        return $imageUrls[0] ?? null;
    }
}
