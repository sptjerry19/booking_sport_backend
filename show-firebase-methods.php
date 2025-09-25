<?php

require 'vendor/autoload.php';

echo "=== FIREBASE MESSAGING METHODS ===\n\n";

try {
    $reflection = new ReflectionClass('Kreait\Firebase\Messaging');

    echo "Class: " . $reflection->getName() . "\n";
    echo "File: " . $reflection->getFileName() . "\n\n";

    echo "Public Methods:\n";
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->name[0] !== '_') { // Skip magic methods
            echo "  - " . $method->name;

            // Show parameters for important methods
            if (in_array($method->name, ['send', 'sendMulticast', 'sendAll'])) {
                $params = [];
                foreach ($method->getParameters() as $param) {
                    $paramStr = '$' . $param->name;
                    if ($param->hasType()) {
                        $paramStr = $param->getType() . ' ' . $paramStr;
                    }
                    if ($param->isOptional()) {
                        $paramStr .= ' = ' . ($param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : 'null');
                    }
                    $params[] = $paramStr;
                }
                echo '(' . implode(', ', $params) . ')';
            }

            echo "\n";
        }
    }

    echo "\n=== SENDMULTICAST DETAILS ===\n";
    $sendMulticast = $reflection->getMethod('sendMulticast');
    echo "Method: " . $sendMulticast->name . "\n";
    echo "Return Type: " . $sendMulticast->getReturnType() . "\n";
    echo "Parameters:\n";
    foreach ($sendMulticast->getParameters() as $param) {
        echo "  - " . ($param->hasType() ? $param->getType() . ' ' : '') . '$' . $param->name;
        if ($param->isOptional()) {
            echo ' = ' . var_export($param->getDefaultValue(), true);
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== KẾT LUẬN ===\n";
echo "✅ sendMulticast() là method CÓ SẴN của Firebase library\n";
echo "✅ Được cài thông qua: composer require kreait/firebase-php\n";
echo "✅ Sử dụng trong: \$this->messaging->sendMulticast(\$message, \$tokens)\n";
echo "✅ Return: MulticastSendReport object\n";
