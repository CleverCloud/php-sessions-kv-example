<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$kvHost = getenv('REDIS_HOST');
$kvType = (substr($kvHost, 0, 9) === 'materiakv') ? 'Materia KV' : 'Redis';
$kvPort = ($kvType === 'Materia KV') ? 6378 : intval(getenv('REDIS_PORT'));
$kvPassword = getenv('REDIS_PASSWORD');

if ($kvHost === false || $kvPort === false || $kvPassword === false) {
    die('One or more required environment variables are not set (REDIS_HOST, REDIS_PORT, REDIS_PASSWORD).');
}

$redisProtocol = 'tcp';

define('REDIS_TIMEOUT', 1);
define('SESSION_LIFETIME', 30);

$redis = new Redis();

try {

    $redis->connect($kvHost, $kvPort, REDIS_TIMEOUT, '', 100, 0, ['auth' => $kvPassword]);
    error_log("Successfully authenticated with {$kvType}");

    $savePath = "{$redisProtocol}://{$kvHost}:{$kvPort}?auth={$kvPassword}";

    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', $savePath);

    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);

    error_log('Starting session...');
    session_start();
    error_log('Session started successfully');

    if (!isset($_SESSION['visits'])) {
        $_SESSION['visits'] = 0;
    }

    $_SESSION['visits']++;

    $sessionId = session_id();
    $ttl = $redis->ttl('PHPREDIS_SESSION:' . $sessionId);

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KV session tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
        }
        .info {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        #sessionInfo {
            font-weight: bold;
            color: #e74c3c;
        }
        pre {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>KV session example</h1>
    <div class="info">
        <p>You have visited this page ' . $_SESSION['visits'] . ' times</p>
        <p id="sessionInfo"></p>
    </div>
    <h2>Raw session data in ' . $kvType . ':</h2>
    <pre>';

    $sessionData = $redis->get('PHPREDIS_SESSION:' . $sessionId);
    var_dump($sessionData);

    echo '</pre>

    <script>
    var sessionLifetime = ' . $ttl . ';
    function updateSessionTime() {
        if (sessionLifetime > 0) {
            document.getElementById("sessionInfo").innerHTML = `Session will expire in ${sessionLifetime} seconds`;
            sessionLifetime--;
            setTimeout(updateSessionTime, 1000);
        } else {
            document.getElementById("sessionInfo").innerHTML = "Session has expired";
        }
    }
    updateSessionTime();
    </script>
</body>
</html>';

} catch (Exception $e) {
    die('Error: ' . $e->getMessage() . '<br>Redis error: ' . $redis->getLastError());
} finally {
    if (isset($redis) && $redis->isConnected()) {
        $redis->close();
    }
}
?>
