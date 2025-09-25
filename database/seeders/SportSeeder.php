<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                'name' => 'Bóng đá',
                'slug' => 'bong-da',
                'description' => 'Môn thể thao vua với 11 người mỗi đội, sử dụng chân để đá bóng vào khung thành đối phương.',
                'icon' => '⚽',
                'positions' => ['Thủ môn', 'Hậu vệ trái', 'Hậu vệ phải', 'Trung vệ', 'Tiền vệ phòng ngự', 'Tiền vệ tấn công', 'Tiền vệ cánh trái', 'Tiền vệ cánh phải', 'Tiền đạo', 'Tiền đạo cắm'],
                'min_players' => 2,
                'max_players' => 22,
            ],
            [
                'name' => 'Bóng rổ',
                'slug' => 'bong-ro',
                'description' => 'Môn thể thao đồng đội với mục tiêu ném bóng vào rổ của đối phương để ghi điểm.',
                'icon' => '🏀',
                'positions' => ['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center'],
                'min_players' => 2,
                'max_players' => 10,
            ],
            [
                'name' => 'Tennis',
                'slug' => 'tennis',
                'description' => 'Môn thể thao dùng vợt đánh bóng qua lưới, có thể chơi đơn hoặc đôi.',
                'icon' => '🎾',
                'positions' => ['Singles Player', 'Doubles Player 1', 'Doubles Player 2'],
                'min_players' => 2,
                'max_players' => 4,
            ],
            [
                'name' => 'Cầu lông',
                'slug' => 'cau-long',
                'description' => 'Môn thể thao sử dụng vợt để đánh cầu qua lưới cao.',
                'icon' => '🏸',
                'positions' => ['Singles Player', 'Doubles Player 1', 'Doubles Player 2'],
                'min_players' => 2,
                'max_players' => 4,
            ],
            [
                'name' => 'Bóng chuyền',
                'slug' => 'bong-chuyen',
                'description' => 'Môn thể thao đồng đội với 6 người mỗi đội, mục tiêu đưa bóng qua lưới và chạm đất sân đối phương.',
                'icon' => '🏐',
                'positions' => ['Libero', 'Setter', 'Outside Hitter', 'Middle Blocker', 'Opposite Hitter', 'Defensive Specialist'],
                'min_players' => 6,
                'max_players' => 12,
            ],
            [
                'name' => 'Bóng bàn',
                'slug' => 'bong-ban',
                'description' => 'Môn thể thao sử dụng vợt nhỏ để đánh bóng nhỏ trên bàn có lưới ở giữa.',
                'icon' => '🏓',
                'positions' => ['Singles Player', 'Doubles Player 1', 'Doubles Player 2'],
                'min_players' => 2,
                'max_players' => 4,
            ],
            [
                'name' => 'Bơi lội',
                'slug' => 'boi-loi',
                'description' => 'Môn thể thao di chuyển trong nước bằng các kỹ thuật bơi khác nhau.',
                'icon' => '🏊',
                'positions' => ['Freestyle', 'Backstroke', 'Breaststroke', 'Butterfly', 'Individual Medley'],
                'min_players' => 1,
                'max_players' => 50,
            ],
            [
                'name' => 'Gym/Fitness',
                'slug' => 'gym-fitness',
                'description' => 'Hoạt động thể dục thể thao nhằm tăng cường sức khỏe và thể lực.',
                'icon' => '🏋️',
                'positions' => ['Cardio', 'Weight Training', 'CrossFit', 'Yoga', 'Pilates', 'Aerobics'],
                'min_players' => 1,
                'max_players' => 30,
            ],
            [
                'name' => 'Futsal',
                'slug' => 'futsal',
                'description' => 'Biến thể của bóng đá được chơi trong nhà với 5 người mỗi đội trên sân nhỏ hơn.',
                'icon' => '⚽',
                'positions' => ['Thủ môn', 'Cánh trái', 'Cánh phải', 'Trung vệ', 'Pivot'],
                'min_players' => 6,
                'max_players' => 10,
            ],
            [
                'name' => 'Pickleball',
                'slug' => 'pickleball',
                'description' => 'Môn thể thao kết hợp tennis, cầu lông và bóng bàn, chơi bằng vợt gỗ và bóng nhựa.',
                'icon' => '🏓',
                'positions' => ['Singles Player', 'Doubles Player 1', 'Doubles Player 2'],
                'min_players' => 2,
                'max_players' => 4,
            ],
            [
                'name' => 'Golf',
                'slug' => 'golf',
                'description' => 'Môn thể thao sử dụng gậy để đánh bóng vào lỗ với ít gậy nhất.',
                'icon' => '⛳',
                'positions' => ['Single Player', 'Team Player'],
                'min_players' => 1,
                'max_players' => 4,
            ],
            [
                'name' => 'Squash',
                'slug' => 'squash',
                'description' => 'Môn thể thao trong phòng kín, người chơi đánh bóng cao su vào tường.',
                'icon' => '🎾',
                'positions' => ['Player 1', 'Player 2'],
                'min_players' => 2,
                'max_players' => 2,
            ],
            [
                'name' => 'Boxing',
                'slug' => 'boxing',
                'description' => 'Môn võ thuật sử dụng nắm đấm, tập luyện thể lực và kỹ năng tự vệ.',
                'icon' => '🥊',
                'positions' => ['Boxer', 'Trainer', 'Sparring Partner'],
                'min_players' => 1,
                'max_players' => 20,
            ],
            [
                'name' => 'Karate',
                'slug' => 'karate',
                'description' => 'Môn võ thuật truyền thống Nhật Bản, sử dụng tay chân để tấn công và phòng thủ.',
                'icon' => '🥋',
                'positions' => ['Student', 'Instructor', 'Sparring Partner'],
                'min_players' => 1,
                'max_players' => 30,
            ],
            [
                'name' => 'Yoga',
                'slug' => 'yoga',
                'description' => 'Hệ thống rèn luyện thân thể và tinh thần thông qua các tư thế và hơi thở.',
                'icon' => '🧘',
                'positions' => ['Practitioner', 'Instructor'],
                'min_players' => 1,
                'max_players' => 25,
            ],
            [
                'name' => 'Muay Thai',
                'slug' => 'muay-thai',
                'description' => 'Môn võ thuật Thái Lan sử dụng nắm đấm, cùi chỏ, đầu gối và ống quyển.',
                'icon' => '🥊',
                'positions' => ['Fighter', 'Trainer', 'Sparring Partner'],
                'min_players' => 1,
                'max_players' => 15,
            ],
            [
                'name' => 'Dance/Aerobic',
                'slug' => 'dance-aerobic',
                'description' => 'Hoạt động thể dục kết hợp nhảy múa và các bài tập thể dục nhịp điệu.',
                'icon' => '💃',
                'positions' => ['Dancer', 'Instructor', 'Choreographer'],
                'min_players' => 1,
                'max_players' => 40,
            ],
            [
                'name' => 'Billiards',
                'slug' => 'billiards',
                'description' => 'Môn thể thao sử dụng cơ để đánh bóng trên bàn phủ nỉ có túi ở các góc.',
                'icon' => '🎱',
                'positions' => ['Player 1', 'Player 2', 'Team Player'],
                'min_players' => 2,
                'max_players' => 4,
            ],
            [
                'name' => 'Bowling',
                'slug' => 'bowling',
                'description' => 'Môn thể thao lăn bóng nặng để hạ gục các kegel được xếp ở cuối đường bowling.',
                'icon' => '🎳',
                'positions' => ['Single Player', 'Team Player'],
                'min_players' => 1,
                'max_players' => 6,
            ],
            [
                'name' => 'Climbing',
                'slug' => 'climbing',
                'description' => 'Môn thể thao leo núi nhân tạo hoặc tự nhiên, rèn luyện sức mạnh và kỹ năng.',
                'icon' => '🧗',
                'positions' => ['Climber', 'Belayer', 'Instructor'],
                'min_players' => 1,
                'max_players' => 8,
            ],
        ];

        foreach ($sports as $sportData) {
            Sport::firstOrCreate(
                ['slug' => $sportData['slug']],
                $sportData
            );
        }

        $this->command->info('Created ' . count($sports) . ' sports successfully!');
    }
}
