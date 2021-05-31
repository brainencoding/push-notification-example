<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Дальше подтягиваем с бд данные юзера и пихаем их в функцию Subscription::create как ассоциативный массив!
// не то либа ругается
// 
// $auth = array(
//     'VAPID' => array(
//         'subject' => 'localhost',
//         'publicKey' => file_get_contents(__DIR__ . '/../keys/public_key.txt'), 
//         'privateKey' => file_get_contents(__DIR__ . '/../keys/private_key.txt'), 
//     ),
// );
// Дальше subject как я понял это адрес сервера + https ключи
// уведомления работают искулючительно на https
// так как сервис воркеры в браузере работают только на хттпс или локалхост
// 
// !!! Поискать метод для отправки для пачки юзеров а не для одного !!!
// 
// Пример юзера который приходит после нажатия кнопи разрешить получение уведомлений
//     {
//       "endpoint": "https://fcm.googleapis.com/fcm/send/eOhjHIdRjvI:APA91bGyzqra1ZOQFAwZtxnXZ5AERUjdHRBGyi-dNNVBxYqVvnKtPXXDPF9GaPIATr9fckYNnHyRdtZsRWw0D6z8v0U4DDCU3JCUk0rXtp_TKT2xaQrpwQQVV7LYk6YYhLwHU9nXr95-",
//       "expirationTime": null,
//       "keys": {
//         "p256dh": "BJe13LO0S4I32z53Gbw6-QyNrN3ViOwC_zQN6pQV-0wehl9oMrb_Hqc0OriEHNp4wsO43bP_yR1OEkiKs4vXRHY",
//         "auth": "s4XGn11MpudI86H8kBB7Pw"
//       },
//       "contentEncoding": "aes128gcm"
//     }
  

$subscription = Subscription::create(json_decode(file_get_contents('php://input'), true));

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
