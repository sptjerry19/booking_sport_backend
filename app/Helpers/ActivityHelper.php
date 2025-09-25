<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class ActivityHelper
{
    /**
     * Log activity (placeholder for Spatie Activity Log)
     */
    public static function log(string $message, $subject = null, $causer = null): void
    {
        // For now, just log to Laravel log
        // In production, you would install spatie/laravel-activitylog
        $context = [
            'subject' => $subject ? get_class($subject) . ':' . $subject->id : null,
            'causer' => $causer ? get_class($causer) . ':' . $causer->id : null,
        ];

        Log::info("Activity: {$message}", array_filter($context));
    }

    /**
     * Get activity builder (placeholder)
     */
    public static function activity()
    {
        return new class {
            private $subject;
            private $causer;

            public function performedOn($subject)
            {
                $this->subject = $subject;
                return $this;
            }

            public function causedBy($causer)
            {
                $this->causer = $causer;
                return $this;
            }

            public function log(string $message)
            {
                ActivityHelper::log($message, $this->subject, $this->causer);
            }
        };
    }
}
