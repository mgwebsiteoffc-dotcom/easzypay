<?php
echo "PHP is working. Version: " . phpversion();
echo "<br>Time: " . date('Y-m-d H:i:s');
echo "<br>Server: " . ($_SERVER['SERVER_NAME'] ?? 'unknown');