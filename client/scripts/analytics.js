(function () {
  // Script deaktivieren für localhost
  if (
    location.hostname === "localhost" ||
    location.hostname === "127.0.0.1" ||
    location.hostname.endsWith(".test")
  ) {
    return; // ⬅️ verhindert alle weiteren Aktionen
  }

  function getDeviceType() {
    const ua = navigator.userAgent;
    if (/Mobi|Android/i.test(ua)) return 'Mobile';
    if (/Tablet|iPad/i.test(ua)) return 'Tablet';
    return 'Desktop';
  }

  const browserData = {
    timestamp: new Date().toISOString(),
    deviceType: getDeviceType(),
    userAgent: navigator.userAgent,
    platform: navigator.platform,
    language: navigator.language,
    timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    screen: {
      width: screen.width,
      height: screen.height,
      pixelRatio: window.devicePixelRatio
    },
    referrer: document.referrer || null
  };

  fetch('server/php/log.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(browserData)
  }).catch(console.error);
})();
