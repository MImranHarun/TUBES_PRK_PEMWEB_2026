(function () {
  var tema = 'sky-bubblegum';
  try {
    var s = localStorage.getItem('dq-tema');
    if (['lavender-dream', 'mint-cloud', 'peach-pop', 'sky-bubblegum'].indexOf(s) > -1) {
      tema = s;
    }
  } catch (e) {
    // jika penyimpanan diblokir peramban, gunakan tema default
  }
  document.documentElement.setAttribute('data-tema', tema);
})();
