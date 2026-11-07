// Toca um áudio por vez: ao dar play em um naipe, pausa os demais.
document.addEventListener('play', function (ev) {
  document.querySelectorAll('audio').forEach(function (a) {
    if (a !== ev.target) a.pause();
  });
}, true);

// Confirmação para formulários marcados com data-confirmar (ex.: excluir).
document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
  f.addEventListener('submit', function (ev) {
    if (!confirm(f.dataset.confirmar)) ev.preventDefault();
  });
});
