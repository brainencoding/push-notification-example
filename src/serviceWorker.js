self.addEventListener('push', function (event) {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  const sendNotification = (body) => {
    const title = 'Бла бла бла это заголовог';

    return self.registration.showNotification(title, {
      body,
    });
  };

  if (event.data) {
    const message = event.data.text();

    event.waitUntil(sendNotification(message));
  }
});
