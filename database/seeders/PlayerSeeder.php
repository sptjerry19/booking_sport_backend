<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating player users...');

        // Vietnamese first names
        $firstNames = [
            'Nguyễn',
            'Trần',
            'Lê',
            'Phạm',
            'Hoàng',
            'Huỳnh',
            'Phan',
            'Vũ',
            'Võ',
            'Đặng',
            'Bùi',
            'Đỗ',
            'Hồ',
            'Ngô',
            'Dương',
            'Lý',
            'Tạ',
            'Đinh',
            'Mai',
            'Chu'
        ];

        // Vietnamese middle/last names
        $lastNames = [
            'Văn',
            'Thị',
            'Hoàng',
            'Minh',
            'Thanh',
            'Thu',
            'Hùng',
            'Dũng',
            'Quang',
            'Hương',
            'Linh',
            'Anh',
            'Tuấn',
            'Hải',
            'Long',
            'Nam',
            'Phong',
            'Trang',
            'Lan',
            'Hoa'
        ];

        $finalNames = [
            'An',
            'Bình',
            'Cường',
            'Đức',
            'Em',
            'Giang',
            'Hạnh',
            'Khánh',
            'Linh',
            'Mai',
            'Nam',
            'Oanh',
            'Phúc',
            'Quân',
            'Sơn',
            'Tâm',
            'Uyên',
            'Vân',
            'Xuân',
            'Yến'
        ];

        // Sports preferences
        $sportsPreferences = [
            ['bong-da'],
            ['bong-ro'],
            ['tennis'],
            ['cau-long'],
            ['bong-chuyen'],
            ['bong-da', 'futsal'],
            ['bong-ro', 'bong-chuyen'],
            ['tennis', 'cau-long'],
            ['gym-fitness', 'yoga'],
            ['boi-loi'],
            ['bong-ban'],
            ['boxing', 'muay-thai']
        ];

        // Position preferences based on sports
        $positionPreferences = [
            'bong-da' => ['Thủ môn', 'Hậu vệ', 'Tiền vệ', 'Tiền đạo'],
            'bong-ro' => ['Point Guard', 'Shooting Guard', 'Forward', 'Center'],
            'bong-chuyen' => ['Libero', 'Setter', 'Outside Hitter', 'Middle Blocker'],
            'futsal' => ['Thủ môn', 'Cánh', 'Trung vệ', 'Pivot'],
            'default' => ['Player', 'Team Member']
        ];

        $levels = ['beginner', 'intermediate', 'advanced', 'professional'];
        $createdCount = 0;

        // Create 200 player users
        for ($i = 1; $i <= 200; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $middleName = $lastNames[array_rand($lastNames)];
            $lastName = $finalNames[array_rand($finalNames)];
            $fullName = "{$firstName} {$middleName} {$lastName}";

            $email = Str::slug($fullName) . $i . '@player.com';

            // Skip if email already exists
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $preferredSports = $sportsPreferences[array_rand($sportsPreferences)];
            $positions = [];

            // Get positions for preferred sports
            foreach ($preferredSports as $sport) {
                if (isset($positionPreferences[$sport])) {
                    $positions = array_merge($positions, $positionPreferences[$sport]);
                } else {
                    $positions = array_merge($positions, $positionPreferences['default']);
                }
            }

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => 'password',
                'level' => $levels[array_rand($levels)],
                'phone' => '+849' . sprintf('%08d', rand(10000000, 99999999)),
                'preferred_sports' => $preferredSports,
                'preferred_position' => array_slice(array_unique($positions), 0, 3), // Max 3 positions
            ]);

            $user->assignRole('player');
            $createdCount++;
        }

        $this->command->info("Created {$createdCount} player users successfully!");
    }
}
