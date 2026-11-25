document.addEventListener('DOMContentLoaded', function () {
  var select = document.getElementById('gnn_mailer_type');
  var sections = document.querySelectorAll('.gnn-settings-section');

  if (select && sections.length) {
    select.addEventListener('change', function () {
      var allowed = ['custom', 'brevo'];
      var type = allowed.indexOf(select.value) !== -1 ? select.value : 'custom';

      sections.forEach(function (section) {
        section.hidden = section.id !== 'gnn_section_' + type;
      });
    });
  }

  var switches = document.querySelectorAll('.gnn-switch-input');
  switches.forEach(function (input) {
    input.addEventListener('change', function () {
      input.setAttribute('aria-checked', input.checked ? 'true' : 'false');
      var label = input.closest('.gnn-switch');
      if (label) {
        var stateEl = label.querySelector('.gnn-switch-state');
        if (stateEl) {
          var onText = stateEl.getAttribute('data-on') || 'AÇIK';
          var offText = stateEl.getAttribute('data-off') || 'KAPALI';
          stateEl.textContent = input.checked ? onText : offText;
        }
      }
    });
  });

  var customTestToggle = document.getElementById('gnn_test_custom');
  var customTestFields = document.getElementById('gnn_test_custom_fields');
  if (customTestToggle && customTestFields) {
    customTestToggle.addEventListener('change', function () {
      customTestFields.hidden = !customTestToggle.checked;
    });
  }

  var authToggle = document.getElementById('gnn_smtp_auth');
  var authFields = document.getElementById('gnn_smtp_auth_fields');
  if (authToggle && authFields) {
    authToggle.addEventListener('change', function () {
      authFields.hidden = !authToggle.checked;
    });
  }

  var presetButtons = document.querySelectorAll('.gnn-preset-btn');
  if (presetButtons.length) {
    var hostInput = document.getElementById('gnn_smtp_host');
    var portInput = document.getElementById('gnn_smtp_port');
    var secureSelect = document.getElementById('gnn_smtp_secure');

    presetButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (hostInput) {
          hostInput.value = btn.getAttribute('data-host') || '';
        }
        if (portInput) {
          portInput.value = btn.getAttribute('data-port') || '587';
        }
        if (secureSelect) {
          secureSelect.value = btn.getAttribute('data-secure') || 'tls';
        }
        if (authToggle) {
          authToggle.checked = btn.getAttribute('data-auth') === '1';
          authToggle.dispatchEvent(new Event('change'));
        }
      });
    });
  }

  var confirmForms = document.querySelectorAll('.gnn-confirm-clear');
  confirmForms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var msg = form.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });
});
