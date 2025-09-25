<?php

namespace Database\Seeders;

use App\Models\Venue;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use App\Models\PricingRule;
use App\Models\TimeSlot;
use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed venues, courts, and related data...');

        // Create additional owner users for more venues
        $this->createAdditionalOwners();

        $owners = User::role('owner')->get();
        $sports = Sport::all();

        if ($owners->isEmpty() || $sports->isEmpty()) {
            $this->command->error('Missing required data. Run RolePermissionSeeder and SportSeeder first.');
            return;
        }

        $locations = $this->getVietnamLocations();
        $venueTemplates = $this->getVenueTemplates();

        $totalVenues = 0;
        $totalCourts = 0;
        $totalPricingRules = 0;
        $totalTimeSlots = 0;
        $totalBookings = 0;

        // Create venues for each owner
        foreach ($owners as $owner) {
            $venueCount = rand(1, 3);

            for ($v = 0; $v < $venueCount; $v++) {
                $template = $venueTemplates[array_rand($venueTemplates)];
                $location = $locations[array_rand($locations)];

                $venue = $this->createVenue($owner, $template, $location);
                $totalVenues++;

                $courtCount = rand(2, 8);

                for ($c = 0; $c < $courtCount; $c++) {
                    $sport = $sports->random();
                    $court = $this->createCourt($venue, $sport, $c + 1);
                    $totalCourts++;

                    $pricingRules = $this->createPricingRules($court, $sport);
                    $totalPricingRules += count($pricingRules);

                    $timeSlots = $this->generateTimeSlots($court, $pricingRules);
                    $totalTimeSlots += count($timeSlots);

                    $bookings = $this->createRandomBookings($court, $timeSlots);
                    $totalBookings += count($bookings);
                }
            }
        }

        $this->command->info("Successfully created:");
        $this->command->info("- {$totalVenues} venues");
        $this->command->info("- {$totalCourts} courts");
        $this->command->info("- {$totalPricingRules} pricing rules");
        $this->command->info("- {$totalTimeSlots} time slots");
        $this->command->info("- {$totalBookings} bookings");
        $this->command->info("Total records: " . ($totalVenues + $totalCourts + $totalPricingRules + $totalTimeSlots + $totalBookings));
    }

    private function createAdditionalOwners()
    {
        $ownerNames = [
            'Nguyễn Văn Sport',
            'Trần Thị Fitness',
            'Lê Hoàng Tennis',
            'Phạm Minh Football',
            'Hoàng Thị Basketball',
            'Vũ Văn Swimming',
            'Đặng Thị Badminton',
            'Bùi Minh Gym',
            'Dương Văn Volleyball',
            'Ngô Thị Yoga',
            'Lý Hoàng Boxing',
            'Tạ Văn Golf',
            'Đinh Thị Dance',
            'Đỗ Minh Climbing',
            'Mai Văn Bowling',
            'Chu Thị Billiards',
            'Hồ Hoàng Karate',
            'Lương Văn Squash',
            'Phan Thị Pickleball',
            'Trương Minh Muay Thai'
        ];

        foreach ($ownerNames as $name) {
            $email = Str::slug($name) . '@owner.com';
            $owner = User::firstOrCreate([
                'email' => $email
            ], [
                'name' => $name,
                'password' => 'password',
                'level' => ['intermediate', 'advanced', 'professional'][rand(0, 2)],
                'phone' => '+849' . sprintf('%08d', rand(10000000, 99999999)),
            ]);
            $owner->assignRole('owner');
        }
    }

    private function getVietnamLocations()
    {
        return [
            ['city' => 'TP. Hồ Chí Minh', 'district' => 'Quận 1', 'lat' => 10.7769, 'lng' => 106.7009],
            ['city' => 'TP. Hồ Chí Minh', 'district' => 'Quận 3', 'lat' => 10.7756, 'lng' => 106.6879],
            ['city' => 'TP. Hồ Chí Minh', 'district' => 'Quận 7', 'lat' => 10.7378, 'lng' => 106.7217],
            ['city' => 'TP. Hồ Chí Minh', 'district' => 'Quận Bình Thạnh', 'lat' => 10.8011, 'lng' => 106.7100],
            ['city' => 'TP. Hồ Chí Minh', 'district' => 'Quận Tân Bình', 'lat' => 10.8008, 'lng' => 106.6526],
            ['city' => 'Hà Nội', 'district' => 'Quận Hoàn Kiếm', 'lat' => 21.0285, 'lng' => 105.8542],
            ['city' => 'Hà Nội', 'district' => 'Quận Ba Đình', 'lat' => 21.0348, 'lng' => 105.8363],
            ['city' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'lat' => 21.0333, 'lng' => 105.7947],
            ['city' => 'Đà Nẵng', 'district' => 'Quận Hải Châu', 'lat' => 16.0544, 'lng' => 108.2022],
            ['city' => 'Cần Thơ', 'district' => 'Quận Ninh Kiều', 'lat' => 10.0452, 'lng' => 105.7469],
        ];
    }

    private function getVenueTemplates()
    {
        return [
            [
                'name_prefix' => 'Sân thể thao',
                'type' => 'multi_sport',
                'amenities' => ['parking', 'shower', 'locker', 'canteen', 'wifi'],
                'description' => 'Khu liên hợp thể thao đa năng với đầy đủ tiện nghi hiện đại.',
            ],
            [
                'name_prefix' => 'Trung tâm thể dục',
                'type' => 'fitness',
                'amenities' => ['parking', 'shower', 'locker', 'sauna', 'massage', 'wifi'],
                'description' => 'Trung tâm thể dục cao cấp với trang thiết bị hiện đại.',
            ],
            [
                'name_prefix' => 'Câu lạc bộ',
                'type' => 'club',
                'amenities' => ['parking', 'shower', 'locker', 'restaurant', 'bar', 'wifi'],
                'description' => 'Câu lạc bộ thể thao sang trọng với dịch vụ 5 sao.',
            ],
        ];
    }

    private function createVenue($owner, $template, $location)
    {
        $venueNames = ['Golden Star', 'Diamond', 'Platinum', 'Royal', 'Elite', 'Premium'];
        $venueName = $template['name_prefix'] . ' ' . $venueNames[array_rand($venueNames)];
        $slug = Str::slug($venueName . ' ' . $location['district']);

        return Venue::create([
            'owner_id' => $owner->id,
            'name' => $venueName,
            'slug' => $slug,
            'description' => $template['description'],
            'address' => $this->generateAddress($location),
            'latitude' => $location['lat'] + (rand(-100, 100) / 10000),
            'longitude' => $location['lng'] + (rand(-100, 100) / 10000),
            'phone' => '+849' . sprintf('%08d', rand(10000000, 99999999)),
            'email' => Str::slug($venueName) . '@venue.com',
            'amenities' => $template['amenities'],
            'images' => ['https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b'],
            'opening_time' => '06:00:00',
            'closing_time' => '22:00:00',
            'status' => ['active', 'active', 'active', 'pending_approval'][rand(0, 3)],
        ]);
    }

    private function generateAddress($location)
    {
        $streets = ['Lê Lợi', 'Nguyễn Huệ', 'Trần Hưng Đạo', 'Hai Bà Trưng'];
        $street = $streets[array_rand($streets)];
        $number = rand(100, 999);
        return "{$number} {$street}, {$location['district']}, {$location['city']}";
    }

    private function createCourt($venue, $sport, $courtNumber)
    {
        return Court::create([
            'venue_id' => $venue->id,
            'sport_id' => $sport->id,
            'name' => "Sân {$sport->name} {$courtNumber}",
            'code' => strtoupper(substr($sport->slug, 0, 2)) . $courtNumber,
            'description' => "Sân {$sport->name} chất lượng cao",
            'surface_type' => ['grass', 'synthetic', 'wood', 'concrete'][rand(0, 3)],
            'dimensions' => ['length' => 20, 'width' => 10, 'unit' => 'm'],
            'hourly_rate' => rand(100, 300),
            'is_active' => true,
            'images' => ['https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b'],
        ]);
    }

    private function createPricingRules($court, $sport)
    {
        $rules = [];

        $rules[] = PricingRule::create([
            'court_id' => $court->id,
            'name' => 'Sáng thường ngày',
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '06:00:00',
            'end_time' => '12:00:00',
            'price_per_hour' => $court->hourly_rate * 0.8,
            'slot_duration_minutes' => 90,
            'is_active' => true,
            'priority' => 1,
        ]);

        $rules[] = PricingRule::create([
            'court_id' => $court->id,
            'name' => 'Tối thường ngày',
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'price_per_hour' => $court->hourly_rate * 1.2,
            'slot_duration_minutes' => 90,
            'is_active' => true,
            'priority' => 2,
        ]);

        $rules[] = PricingRule::create([
            'court_id' => $court->id,
            'name' => 'Cuối tuần',
            'days_of_week' => [6, 7],
            'start_time' => '06:00:00',
            'end_time' => '22:00:00',
            'price_per_hour' => $court->hourly_rate * 1.5,
            'slot_duration_minutes' => 90,
            'is_active' => true,
            'priority' => 3,
        ]);

        return $rules;
    }

    private function generateTimeSlots($court, $pricingRules)
    {
        $slots = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek;

            $applicableRules = collect($pricingRules)->filter(function ($rule) use ($dayOfWeek) {
                return in_array($dayOfWeek, $rule->days_of_week);
            });

            foreach ($applicableRules as $rule) {
                $startTime = Carbon::createFromTimeString($rule->start_time);
                $endTime = Carbon::createFromTimeString($rule->end_time);
                $slotDuration = $rule->slot_duration_minutes;

                while ($startTime->lt($endTime)) {
                    $slotEndTime = $startTime->copy()->addMinutes($slotDuration);

                    if ($slotEndTime->lte($endTime)) {
                        $slots[] = TimeSlot::create([
                            'court_id' => $court->id,
                            'date' => $date->format('Y-m-d'),
                            'start_time' => $startTime->format('H:i:s'),
                            'end_time' => $slotEndTime->format('H:i:s'),
                            'price' => $rule->price_per_hour * ($slotDuration / 60),
                            'status' => 'available',
                            'pricing_rule_id' => $rule->id,
                        ]);
                    }

                    $startTime->addMinutes($slotDuration);
                }
            }
        }

        return $slots;
    }

    private function createRandomBookings($court, $timeSlots)
    {
        if (empty($timeSlots)) {
            return [];
        }

        $bookings = [];
        $players = User::role('player')->get();

        if ($players->isEmpty()) {
            return [];
        }

        $bookingCount = min(count($timeSlots) * 0.3, 50);
        $selectedSlots = collect($timeSlots)->random(min($bookingCount, count($timeSlots)));

        foreach ($selectedSlots as $slot) {
            $player = $players->random();
            $bookingDate = Carbon::parse($slot->date);

            if ($bookingDate->gte(Carbon::today())) {
                $discountAmount = rand(0, 1) ? rand(10, 50) : 0;
                $booking = Booking::create([
                    'user_id' => $player->id,
                    'court_id' => $court->id,
                    'booking_date' => $slot->date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'total_amount' => $slot->price,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $slot->price - $discountAmount,
                    'status' => ['pending', 'confirmed', 'paid'][rand(0, 2)],
                    'payment_status' => ['pending', 'paid'][rand(0, 1)],
                    'payment_method' => ['cash', 'bank_transfer', 'credit_card'][rand(0, 2)],
                    'notes' => rand(0, 1) ? 'Đặt sân cho nhóm bạn' : null,
                ]);

                $slot->update(['status' => 'booked']);
                $bookings[] = $booking;
            }
        }

        return $bookings;
    }
}
