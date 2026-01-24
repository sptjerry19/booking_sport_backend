<?php

namespace App\Console\Commands;

use App\Models\Court;
use App\Models\PricingRule;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTimeSlotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'timeslots:generate {--month= : Tháng cần generate (format: Y-m), mặc định là tháng tiếp theo}';

    /**
     * The description of the console command.
     */
    protected $description = 'Generate time slots cho tất cả courts cho tháng tiếp theo';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Bắt đầu generate time slots...');

        try {
            // Xác định tháng cần generate
            $monthOption = $this->option('month');
            if ($monthOption) {
                $targetMonth = Carbon::createFromFormat('Y-m', $monthOption)->startOfMonth();
            } else {
                // Mặc định: tháng tiếp theo
                $targetMonth = Carbon::now()->addMonth()->startOfMonth();
            }

            $endDate = $targetMonth->copy()->endOfMonth();

            $this->info("Generate time slots từ {$targetMonth->format('Y-m-d')} đến {$endDate->format('Y-m-d')}");

            // Lấy tất cả courts active
            $courts = Court::active()->get();

            if ($courts->isEmpty()) {
                $this->warn('Không tìm thấy court nào active.');
                return self::SUCCESS;
            }

            $totalSlots = 0;
            $totalCourts = $courts->count();

            $bar = $this->output->createProgressBar($totalCourts);
            $bar->start();

            foreach ($courts as $court) {
                $slotsGenerated = $this->generateTimeSlotsForCourt($court, $targetMonth, $endDate);
                $totalSlots += $slotsGenerated;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Hoàn thành! Đã generate {$totalSlots} time slots cho {$totalCourts} courts.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Lỗi khi generate time slots: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Generate time slots cho một court
     */
    private function generateTimeSlotsForCourt(Court $court, Carbon $startDate, Carbon $endDate): int
    {
        $slotsGenerated = 0;

        // Lấy pricing rules active và valid cho court
        $pricingRules = PricingRule::where('court_id', $court->id)
            ->active()
            ->get();

        // Duyệt qua từng ngày trong tháng
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek;

            // Lọc các rules áp dụng cho ngày này
            $applicableRules = $pricingRules->filter(function ($rule) use ($date, $dayOfWeek) {
                // Check day of week
                if (!in_array($dayOfWeek, $rule->days_of_week ?? [])) {
                    return false;
                }

                // Check valid date range
                if ($rule->valid_from && $date->lt($rule->valid_from)) {
                    return false;
                }
                if ($rule->valid_until && $date->gt($rule->valid_until)) {
                    return false;
                }

                return true;
            });

            // Nếu có rules, dùng rules; nếu không có, dùng default
            if ($applicableRules->isNotEmpty()) {
                foreach ($applicableRules as $rule) {
                    $slotsGenerated += $this->createTimeSlotsFromRule($court, $date, $rule);
                }
            } else {
                // Default: 5h đến 24h (00:00), slot duration 60 phút
                $slotsGenerated += $this->createDefaultTimeSlots($court, $date);
            }
        }

        return $slotsGenerated;
    }

    /**
     * Tạo time slots từ pricing rule
     */
    private function createTimeSlotsFromRule(Court $court, Carbon $date, PricingRule $rule): int
    {
        $slotsCreated = 0;
        $startTime = Carbon::createFromTimeString($rule->start_time);
        $endTime = Carbon::createFromTimeString($rule->end_time);
        $slotDuration = $rule->slot_duration_minutes ?? 60;

        while ($startTime->lt($endTime)) {
            $slotEndTime = $startTime->copy()->addMinutes($slotDuration);

            // Xử lý trường hợp end_time là 24:00:00
            if ($endTime->format('H:i:s') === '24:00:00' || $endTime->format('H:i:s') === '00:00:00') {
                // Nếu slot vượt quá 24:00:00, đặt end_time là 24:00:00
                if ($slotEndTime->gte($endTime) || $slotEndTime->format('H:i:s') === '00:00:00') {
                    $slotEndTime = Carbon::createFromTimeString('24:00:00');
                }
            } else {
                // Đảm bảo slot không vượt quá end_time
                if ($slotEndTime->gt($endTime)) {
                    break;
                }
            }

            // Tính giá
            $price = $rule->price_per_hour * ($slotDuration / 60);

            // Tạo time slot (sử dụng updateOrCreate để tránh duplicate)
            try {
                TimeSlot::updateOrCreate(
                    [
                        'court_id' => $court->id,
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $startTime->format('H:i:s'),
                    ],
                    [
                        'end_time' => $slotEndTime->format('H:i:s'),
                        'price' => $price,
                        'status' => 'available',
                        'pricing_rule_id' => $rule->id,
                    ]
                );
                $slotsCreated++;
            } catch (\Exception $e) {
                // Bỏ qua nếu đã tồn tại (unique constraint)
                // Log nếu cần
            }

            $startTime->addMinutes($slotDuration);

            // Dừng nếu đã đến hoặc vượt quá end_time
            if ($startTime->gte($endTime) || ($endTime->format('H:i:s') === '24:00:00' && $startTime->format('H:i:s') === '00:00:00')) {
                break;
            }
        }

        return $slotsCreated;
    }

    /**
     * Tạo time slots mặc định (5h - 24h)
     */
    private function createDefaultTimeSlots(Court $court, Carbon $date): int
    {
        $slotsCreated = 0;
        $startTime = Carbon::createFromTimeString('05:00:00');
        $endTime = Carbon::createFromTimeString('24:00:00'); // 24:00:00 = nửa đêm
        $slotDuration = 60; // 60 phút
        $pricePerHour = $court->hourly_rate ?? 0;

        while ($startTime->lt($endTime)) {
            $slotEndTime = $startTime->copy()->addMinutes($slotDuration);

            // Nếu slot vượt quá 24:00:00, đặt end_time là 24:00:00
            if ($slotEndTime->gte($endTime) || $slotEndTime->format('H:i:s') === '00:00:00') {
                $slotEndTime = Carbon::createFromTimeString('24:00:00');
            }

            // Tính giá
            $hours = $slotDuration / 60;
            $price = $pricePerHour * $hours;

            // Tạo time slot
            try {
                TimeSlot::updateOrCreate(
                    [
                        'court_id' => $court->id,
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $startTime->format('H:i:s'),
                    ],
                    [
                        'end_time' => $slotEndTime->format('H:i:s'),
                        'price' => $price,
                        'status' => 'available',
                        'pricing_rule_id' => null,
                    ]
                );
                $slotsCreated++;
            } catch (\Exception $e) {
                // Bỏ qua nếu đã tồn tại
            }

            $startTime->addMinutes($slotDuration);

            // Dừng nếu đã đến hoặc vượt quá 24:00:00
            if ($startTime->gte($endTime) || $startTime->format('H:i:s') === '00:00:00') {
                break;
            }
        }

        return $slotsCreated;
    }
}
