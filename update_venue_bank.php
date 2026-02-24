<?php

use App\Models\Venue;

$venue = Venue::whereHas('bookings')->first();

if ($venue) {
    echo "Updating venue: {$venue->name} (ID: {$venue->id})\n";
    $venue->update([
        'bank_bin' => '970423', // TPBank example or similar
        'bank_account_no' => '123456789',
        'bank_account_name' => 'NGUYEN VAN A'
    ]);
    echo "Venue updated with bank info.\n";
} else {
    echo "No venue found with bookings.\n";
}
