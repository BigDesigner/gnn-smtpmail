(function($){
  $(document).ready(function(){
    $('#gnn_mailer_type').on('change', function(){
      var type = $(this).val();
      $('.gnn-settings-section').hide();
      $('#gnn_section_' + type).show();
    });
  });
})(jQuery);
