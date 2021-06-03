<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$allUsers = json_decode(file_get_contents("./user.json"),  true);

if (isset($allUsers)) {
    foreach($allUsers['users'] as $user) {
        $subscription = Subscription::create($user);

        $auth = array(
            'VAPID' => array(
                'subject' => 'localhost',
                'publicKey' => file_get_contents(__DIR__ . '/../keys/public_key.txt'), 
                'privateKey' => file_get_contents(__DIR__ . '/../keys/private_key.txt'), 
            ),
        );

        $webPush = new WebPush($auth);

        $report = $webPush->sendOneNotification(
            $subscription,
            "Привет консат инфо"
        );

        $endpoint = $report->getRequest()->getUri()->__toString();

        if ($report->isSuccess()) {
            echo "[v] Message sent successfully for subscription {$endpoint}.";
        } else {
            echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
        }
    }
}
