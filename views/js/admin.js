/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

document.addEventListener('input', function (event) {
  var picker = event.target.closest('[data-m4p-color-for]');
  if (picker) {
    document.getElementById(picker.getAttribute('data-m4p-color-for')).value = picker.value;
    return;
  }

  var text = event.target.closest('.m4p-core-color .form-control');
  if (text && /^#[0-9a-f]{6}$/i.test(text.value)) {
    text.parentNode.querySelector('[data-m4p-color-for]').value = text.value;
  }
});
