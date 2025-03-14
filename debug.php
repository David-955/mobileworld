<?php

// corriger "Attempted to load class "DebugBundle" from namespace "Symfony\Bundle\DebugBundle"'
// Did you forget a "use" statement for another namespace?
echo '<pre>';
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'Not set') . "\n";
echo "DebugBundle installed?: ";
passthru('composer show symfony/debug-bundle 2>&1');
echo '</pre>';
