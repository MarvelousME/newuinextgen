(function ($) {
  'use strict';

  $(document).on('click', '.bi-override-toggle', function () {
    var $item = $(this).closest('.bi-override-item');
    var inherit = !$item.hasClass('bi-override--inherit');
    $item.toggleClass('bi-override--inherit', inherit);
    $item.toggleClass('bi-override--custom', !inherit);
    $item.find('.bi-override-inherit-flag').val(inherit ? '1' : '0');
    $item.find('.bi-override-field').prop('hidden', inherit);
    $(this).attr('aria-expanded', inherit ? 'false' : 'true');
    $(this).text(inherit ? 'Inherit' : 'Custom');
  });
})(jQuery);
