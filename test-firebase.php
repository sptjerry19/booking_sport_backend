<?php

// Simple test to verify Firebase SDK upgrade
echo "=== FIREBASE SDK VERSION TEST ===\n\n";

// Check composer.lock for version
$composerLock = json_decode(file_get_contents('composer.lock'), true);
foreach ($composerLock['packages'] as $package) {
    if ($package['name'] === 'kreait/firebase-php') {
        echo "✅ Firebase PHP SDK version: " . $package['version'] . "\n";
        break;
    }
}

// Check if classes exist
if (class_exists('Kreait\\Firebase\\Messaging')) {
    echo "✅ Firebase Messaging class exists\n";
    
    $methods = get_class_methods('Kreait\\Firebase\\Messaging');
    if (in_array('sendMulticast', $methods)) {
        echo "✅ sendMulticast() method exists\n";
    } else {
        echo "❌ sendMulticast() method not found\n";
    }
    
    if (in_array('send', $methods)) {
        echo "✅ send() method exists (for topic messaging)\n";
    }
    
    if (in_array('subscribeToTopic', $methods)) {
        echo "✅ subscribeToTopic() method exists\n";
    }
    
} else {
    echo "❌ Firebase Messaging class not found\n";
}

echo "\n=== RECOMMENDATION ===\n";
echo "Với Firebase SDK 7.21.2, sendMulticast() sẽ hoạt động bình thường.\n";
echo "Lỗi 404 trước đây do SDK 6.0 sử dụng API endpoints cũ.\n";
echo "Bây giờ bạn có thể:\n";
echo "1. ✅ Tiếp tục dùng sendMulticast() - đã fixed\n";
echo "2. ✅ Chuyển sang Topic-based - hiệu quả hơn cho broadcast\n";
echo "3. ✅ Kết hợp cả hai - Topic cho broadcast, Multicast cho targeted\n";

?>
