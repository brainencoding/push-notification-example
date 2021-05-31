document.addEventListener('DOMContentLoaded', () => {
  /*
    applicationServerKey это публичный ключ сертификата https
  */

  const applicationServerKey =
    'BMBlr6YznhYMX3NgcWIDRxZXs0sh7tCv7_YCsWcww0ZCv9WGg-tRCXfMEHTiBPCksSqeve1twlbmVAZFv7GSuj0';

  let isPushEnabled = false;

  const pushButton = document.querySelector('#push-subscription-button');
  if (!pushButton) {
    return;
  }

  let reg = null;

  pushButton.addEventListener('click', function () {
    if (isPushEnabled) {
      push_unsubscribe();
    } else {
      push_subscribe();
    }
  });

  if (!('serviceWorker' in navigator)) {
    console.warn('Service workers are not supported by this browser');
    changePushButtonState('incompatible');
    return;
  }

  if (!('PushManager' in window)) {
    console.warn('Push notifications are not supported by this browser');
    changePushButtonState('incompatible');
    return;
  }

  if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
    console.warn('Notifications are not supported by this browser');
    changePushButtonState('incompatible');
    return;
  }

  if (Notification.permission === 'denied') {
    console.warn('Notifications are denied by the user');
    changePushButtonState('incompatible');
    return;
  }

  navigator.serviceWorker
    .register('./src/serviceWorker.js', {
      scope: './src/',
    })
    .then(
      (_reg) => {
        console.log('[SW] Service worker has been registered');
        reg = _reg;
        push_updateSubscription(_reg);
      },
      (e) => {
        console.error('[SW] Service worker registration failed', e);
        changePushButtonState('incompatible');
      }
    );

  function changePushButtonState(state) {
    switch (state) {
      case 'enabled':
        pushButton.disabled = false;
        pushButton.textContent = 'Disable Push notifications';
        isPushEnabled = true;
        break;
      case 'disabled':
        pushButton.disabled = false;
        pushButton.textContent = 'Enable Push notifications';
        isPushEnabled = false;
        break;
      case 'computing':
        pushButton.disabled = true;
        pushButton.textContent = 'Loading...';
        break;
      case 'incompatible':
        pushButton.disabled = true;
        pushButton.textContent = 'Push notifications are not compatible with this browser';
        break;
      default:
        console.error('Unhandled push button state', state);
        break;
    }
  }

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  function checkNotificationPermission() {
    return new Promise((resolve, reject) => {
      if (Notification.permission === 'denied') {
        return reject(new Error('Push messages are blocked.'));
      }

      if (Notification.permission === 'granted') {
        return resolve();
      }

      if (Notification.permission === 'default') {
        return Notification.requestPermission().then((result) => {
          if (result !== 'granted') {
            reject(new Error('Bad permission result'));
          } else {
            resolve();
          }
        });
      }

      return reject(new Error('Unknown permission'));
    });
  }

  function push_subscribe() {
    changePushButtonState('computing');

    return checkNotificationPermission()
      .then(() =>
        reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
        })
      )
      .then((subscription) => {
        return push_sendSubscriptionToServer(subscription, 'POST');
      })
      .then((subscription) => subscription && changePushButtonState('enabled')) // update your UI
      .catch((e) => {
        if (Notification.permission === 'denied') {
          console.warn('Notifications are denied by the user.');
          changePushButtonState('incompatible');
        } else {
          console.error('Impossible to subscribe to push notifications', e);
          changePushButtonState('disabled');
        }
      });
  }

  async function push_updateSubscription() {
    reg.pushManager
      .getSubscription()
      .then((subscription) => {
        changePushButtonState('disabled');

        if (!subscription) {
          return;
        }

        return push_sendSubscriptionToServer(subscription, 'PUT');
      })
      .then((subscription) => subscription && changePushButtonState('enabled'))
      .catch((e) => {
        console.error('Error when updating the subscription', e);
      });
  }

  function push_unsubscribe() {
    changePushButtonState('computing');

    reg.pushManager
      .getSubscription()
      .then((subscription) => {
        if (!subscription) {
          changePushButtonState('disabled');
          return;
        }

        return push_sendSubscriptionToServer(subscription, 'DELETE');
      })
      .then((subscription) => subscription.unsubscribe())
      .then(() => changePushButtonState('disabled'))
      .catch((e) => {
        console.error('Error when unsubscribing the user', e);
        changePushButtonState('disabled');
      });
  }

  function push_sendSubscriptionToServer(subscription, method) {
    const key = subscription.getKey('p256dh');
    const token = subscription.getKey('auth');
    const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

    return fetch('src/push_subscription.php', {
      method,
      body: JSON.stringify({
        endpoint: subscription.endpoint,
        publicKey: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
        authToken: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
        contentEncoding,
      }),
    }).then(() => subscription);
  }

  const sendPushButton = document.querySelector('#send-push-button');
  if (!sendPushButton) {
    return;
  }

  sendPushButton.addEventListener('click', () =>
    reg.pushManager.getSubscription().then((subscription) => {
      if (!subscription) {
        alert('Please enable push notifications');
        return;
      }

      const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
      const jsonSubscription = subscription.toJSON();
      fetch('src/send_push_notification.php', {
        method: 'POST',
        body: JSON.stringify(Object.assign(jsonSubscription, { contentEncoding })),
      });
    })
  );
});
