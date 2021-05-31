<?php
$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint'])) {
    echo 'Error: not a subscription';
    return;
}

// На этом моменте можно сохранить пришедший обьект из $subscription в бд и далее в файле send_push_not...
