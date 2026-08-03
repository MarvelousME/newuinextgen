jQuery(function ($) {
  $('#nuapi-scan-btn').on('click', function () {
    var btn = $(this).prop('disabled', true).text('Scanning…');
    $.post(NUAPI.ajaxUrl, { action: 'nuapi_scan', nonce: NUAPI.nonce }, function (res) {
      if (res.success) { location.reload(); }
      else { alert('Scan failed'); btn.prop('disabled', false).text('↻ Rescan Now'); }
    });
  });

  $('.nuapi-toggle-table').on('change', function () {
    var $cb = $(this), table = $cb.data('table'), enabled = $cb.is(':checked');
    $.post(NUAPI.ajaxUrl, { action: 'nuapi_toggle_table', nonce: NUAPI.nonce, table: table, enabled: enabled }, function (res) {
      if (!res.success) { alert('Failed to update'); $cb.prop('checked', !enabled); return; }
      var $writeCb = $cb.closest('tr').find('.nuapi-toggle-write');
      $writeCb.prop('disabled', !enabled);
      if (!enabled) { $writeCb.prop('checked', false); }
    });
  });

  $('.nuapi-toggle-write').on('change', function () {
    var $cb = $(this), table = $cb.data('table'), enabled = $cb.is(':checked');
    $.post(NUAPI.ajaxUrl, { action: 'nuapi_toggle_write', nonce: NUAPI.nonce, table: table, enabled: enabled }, function (res) {
      if (!res.success) { alert('Failed to update'); $cb.prop('checked', !enabled); }
    });
  });

  $('#nuapi-generate-key-btn').on('click', function () {
    var label = $('#nuapi-key-label').val() || 'Untitled Key';
    var scope = $('#nuapi-key-scope').val();
    $.post(NUAPI.ajaxUrl, { action: 'nuapi_generate_key', nonce: NUAPI.nonce, label: label, scope: scope }, function (res) {
      if (res.success) {
        $('#nuapi-new-key-display').show().text('Your new API key (copy it now — it will not be shown again):\n\n' + res.data.key);
        setTimeout(function () { location.reload(); }, 4000);
      } else { alert('Failed to generate key'); }
    });
  });

  $('.nuapi-revoke-key-btn').on('click', function () {
    if (!confirm('Revoke this API key? Any app using it will lose access immediately.')) return;
    var id = $(this).data('id');
    $.post(NUAPI.ajaxUrl, { action: 'nuapi_revoke_key', nonce: NUAPI.nonce, id: id }, function (res) {
      if (res.success) { location.reload(); } else { alert('Failed to revoke'); }
    });
  });

  $('#nuapi-console-send').on('click', function () {
    var method = $('#nuapi-console-method').val();
    var table = $('#nuapi-console-table').val();
    var id = $('#nuapi-console-id').val();
    var body = $('#nuapi-console-body').val();
    var $out = $('#nuapi-console-response');

    if (!table) { $out.text('Choose a table first.'); return; }

    var url = NUAPI.restRoot + '/data/' + table + (id ? '/' + id : '');
    if (method === 'DELETE' && id) { url += '?confirm=true'; }

    var opts = { method: method, headers: { 'X-WP-Nonce': NUAPI.restNonce, 'Content-Type': 'application/json' } };
    if (method === 'POST' || method === 'PUT') {
      try { opts.body = body ? JSON.stringify(JSON.parse(body)) : '{}'; }
      catch (e) { $out.text('Invalid JSON body: ' + e.message); return; }
    }

    $out.text('Sending…');
    fetch(url, opts).then(function (r) {
      return r.json().then(function (json) { return { status: r.status, json: json }; });
    }).then(function (res) {
      $out.text('HTTP ' + res.status + '\n\n' + JSON.stringify(res.json, null, 2));
    }).catch(function (err) {
      $out.text('Request failed: ' + err.message);
    });
  });
});
