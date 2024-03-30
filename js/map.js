document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initYandexMap, 5000);
  });

  document.addEventListener('scroll', initYandexMapOnEvent);
  document.addEventListener('mousemove', initYandexMapOnEvent);
  document.addEventListener('touchstart', initYandexMapOnEvent);

  function initYandexMapOnEvent(e) {
    initYandexMap();
    e.currentTarget.removeEventListener(e.type, initYandexMapOnEvent);
  }

  function initYandexMap() {
    if (window.yandexMapDidInit) {
      return false;
    }
    window.yandexMapDidInit = true;

    document.getElementById('YandexMap').src = 'https://yandex.ru/map-widget/v1/?ll=39.611689%2C47.199678&mode=search&oid=172777415476&ol=biz&z=15.43';
  }