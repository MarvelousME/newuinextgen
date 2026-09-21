(function ($) {
	'use strict';

	$(function () {
		$('#ngt3d-cb-all').on('change', function () {
			$('input[name="rule_ids[]"]').prop('checked', this.checked);
		});

		$('form').on('submit', function () {
			var action = $(this).find('select[name="bulk_action"]').val();
			if (action === 'delete' && window.NGT3D_Admin && NGT3D_Admin.i18n) {
				return window.confirm(NGT3D_Admin.i18n.confirmDelete);
			}
			return true;
		});
	});
})(jQuery);
