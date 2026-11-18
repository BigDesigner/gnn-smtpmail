document.addEventListener('DOMContentLoaded', function () {
  var select = document.getElementById('gnn_mailer_type');
  if (!select) {
    return;
  }

  var sections = document.querySelectorAll('.gnn-settings-section');

  select.addEventListener('change', function () {
    var allowed = ['custom', 'brevo'];
    var type = allowed.indexOf(select.value) !== -1 ? select.value : 'custom';

    sections.forEach(function (section) {
      section.hidden = section.id !== 'gnn_section_' + type;
    });
  });
});
