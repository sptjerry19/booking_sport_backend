<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VenueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'amenities' => $this->amenities,
            'images' => $this->getImageUrls(),
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'status' => $this->status,
            'distance' => $this->when(isset($this->distance), $this->distance),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'courts' => CourtResource::collection($this->whenLoaded('courts')),
            'courts_count' => $this->when(isset($this->courts_count), $this->courts_count),
            'reviews_count' => $this->reviews_count ?? rand(1, 1000),
            'rating' => $this->rating ?? rand(1, 5),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get full URLs for images
     */
    private function getImageUrls(): array
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
}
