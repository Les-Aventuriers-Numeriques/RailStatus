function showModalDialog(title, text, buttons, callback) {
  if (buttons == null) {
    buttons = {
      'Ok': {
        style: 'primary',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    };
  }

  var modal_dialog = $('#modal_dialog');

  modal_dialog.modal({
    show: false
  }).on('show.bs.modal', function() {
    $(this).find('.modal-body').first().html(text);
    $(this).find('.modal-title').first().text(title);

    var modal_footer = $(this).find('.modal-footer').first();

    modal_footer.empty();

    for (var button_title in buttons) {
      var button = buttons[button_title];
      var button_callback = buttons[button_title].callback;

      var button_html = $('<button type="button" class="btn btn-'+button.style+'">'+button_title+'</button>');

      (function(button_callback) {
        button_html.on('click', function() {
          button_callback(modal_dialog);
        });
      })(button_callback);

      modal_footer.append(button_html);
    }

    if (callback != null) {
      callback(modal_dialog);
    }
  }).modal('show');
}