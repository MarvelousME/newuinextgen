(function ($) {
  'use strict';

  var $status = $('#rhi-status');
  var scanData = [];

  function setStatus(msg, type) {
    $status.removeClass('notice-success notice-error notice-info').addClass('notice notice-' + (type || 'info'));
    $status.html('<p>' + msg + '</p>').show();
  }

  function getOptions() {
    return {
      directory: $('#rhi-directory').val(),
      dry_run: $('#rhi-dry-run').is(':checked') ? 1 : 0,
      force: $('#rhi-force').is(':checked') ? 1 : 0,
      publish: $('#rhi-publish').is(':checked') ? 1 : 0,
      nonce: rhiAdmin.nonce
    };
  }

  $('#rhi-scan-btn').on('click', function () {
    setStatus(rhiAdmin.i18n.scanning, 'info');
    $.post(rhiAdmin.ajaxUrl, $.extend({ action: 'rhi_scan' }, getOptions()))
      .done(function (res) {
        if (!res.success) {
          setStatus(res.data && res.data.message ? res.data.message : rhiAdmin.i18n.error, 'error');
          return;
        }
        scanData = res.data.files || [];
        $('#rhi-mapping-body').html(res.data.rows);
        $('#rhi-import-confident-btn, #rhi-import-selected-btn').prop('disabled', false);
        setStatus('Scanned ' + res.data.count + ' HTML file(s).', 'success');
      })
      .fail(function () { setStatus(rhiAdmin.i18n.error, 'error'); });
  });

  function runImport(extra) {
    setStatus(rhiAdmin.i18n.importing, 'info');
    var payload = $.extend({ action: 'rhi_import' }, getOptions(), extra || {});
    $.post(rhiAdmin.ajaxUrl, payload)
      .done(function (res) {
        if (!res.success) {
          setStatus(res.data && res.data.message ? res.data.message : rhiAdmin.i18n.error, 'error');
          return;
        }
        var r = res.data.report;
        var msg = (r.dry_run ? '[DRY RUN] ' : '') +
          'Created: ' + (r.created || []).length +
          ' | Updated: ' + (r.updated || []).length +
          ' | Skipped: ' + (r.skipped || []).length +
          ' | Review: ' + (r.review_required || []).length;
        setStatus(msg, 'success');
        if (r && !r.dry_run) {
          location.reload();
        }
      })
      .fail(function () { setStatus(rhiAdmin.i18n.error, 'error'); });
  }

  $('#rhi-import-confident-btn').on('click', function () {
    if (!confirm('Import all files with confidence ≥ 80%?')) return;
    runImport({ min_confidence: 80 });
  });

  $('#rhi-import-selected-btn').on('click', function () {
    var files = $('.rhi-file-check:checked').map(function () { return $(this).val(); }).get();
    if (!files.length) {
      setStatus('Select at least one file.', 'error');
      return;
    }
    if (!confirm('Import ' + files.length + ' selected file(s)?')) return;
    runImport({ files: files, min_confidence: 0 });
  });

  $('#rhi-select-all').on('change', function () {
    $('.rhi-file-check').prop('checked', $(this).is(':checked'));
  });

  $('#rhi-rollback-btn').on('click', function () {
    if (!confirm('Rollback ALL pages with stored backup content?')) return;
    $.post(rhiAdmin.ajaxUrl, { action: 'rhi_rollback', nonce: rhiAdmin.nonce })
      .done(function (res) {
        if (res.success) {
          setStatus('Restored: ' + res.data.result.restored + ', Failed: ' + res.data.result.failed, 'success');
        }
      });
  });
})(jQuery);
