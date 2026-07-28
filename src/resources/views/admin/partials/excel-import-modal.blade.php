{{--
    Reusable "import from Excel" modal: choose a file -> AJAX preview (server parses +
    validates every row, without saving) -> a sheet-like table showing each row with its
    own status/error -> Save button re-submits the same file to actually persist the valid
    rows, again row by row, updating each row's status in place.

    Required props: modalId, title, previewUrl, saveUrl, columns (array of ['key','label',
    optional 'align']). The row objects returned by the preview/save endpoints must expose
    a value for each column's key, plus 'row' (spreadsheet row number), 'errors' (array),
    and 'saved' (bool).

    Written with event delegation + closest('.modal') scoping (not per-instance ids), so it
    stays correct and collision-free even if included more than once on the same page.
--}}
@php
    $normalizedColumns = collect($columns)->map(fn ($c) => [
        'key' => $c['key'],
        'label' => $c['label'],
        'align' => $c['align'] ?? 'left',
    ])->values();
@endphp
<div class="modal fade excel-import-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true"
    data-preview-url="{{ $previewUrl }}"
    data-save-url="{{ $saveUrl }}"
    data-columns='{{ $normalizedColumns->toJson() }}'>
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-end flex-wrap mb-3">
                    <div>
                        <label class="form-label mb-1">Choose Excel File (.xlsx)</label>
                        <input type="file" class="excel-import-file form-control form-control-sm" accept=".xlsx,.xls">
                    </div>
                    <a href="{{ route('acc-sfl.import-template.download') }}" class="btn btn-light btn-sm">
                        <i class="fa-solid fa-download mr-1"></i> Download Template
                    </a>
                </div>

                <div class="excel-import-loading text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0 text-muted">Processing file&hellip;</p>
                </div>

                <div class="excel-import-error alert alert-danger d-none"></div>
                <div class="excel-import-summary alert alert-info d-none"></div>

                <div class="excel-import-table-wrapper table-responsive" style="display:none; max-height: 50vh; overflow-y: auto;">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                @foreach($normalizedColumns as $column)
                                <th>{{ $column['label'] }}</th>
                                @endforeach
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="excel-import-table-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                <button type="button" class="excel-import-save-btn btn btn-primary btn-sm d-none" disabled>Save Valid Rows</button>
            </div>
        </div>
    </div>
</div>

@push('js')
@once
<script>
    (function () {
        var csrfToken = {!! json_encode(csrf_token()) !!};

        function escapeHtml(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        }

        function resetModal($modal) {
            $modal.find('.excel-import-file').val('');
            $modal.find('.excel-import-error').addClass('d-none').text('');
            $modal.find('.excel-import-summary').addClass('d-none').removeClass('alert-success').addClass('alert-info').text('');
            $modal.find('.excel-import-table-wrapper').hide();
            $modal.find('.excel-import-table-body').empty();
            $modal.find('.excel-import-save-btn').addClass('d-none').prop('disabled', true).text('Save Valid Rows').removeData('selectedFile');
            $modal.find('.excel-import-loading').addClass('d-none');
        }

        function renderRows($modal, rows) {
            var columns = [];
            try {
                columns = JSON.parse($modal.attr('data-columns') || '[]');
            } catch (e) {
                columns = [];
            }

            var $body = $modal.find('.excel-import-table-body');
            $body.empty();

            var validCount = 0;
            var savedCount = 0;

            rows.forEach(function (row) {
                var hasErrors = !!(row.errors && row.errors.length);
                if (!hasErrors) { validCount++; }
                if (row.saved) { savedCount++; }

                var statusHtml;
                if (row.saved) {
                    statusHtml = '<span class="badge badge-success">Saved</span>';
                } else if (hasErrors) {
                    statusHtml = '<span class="badge badge-danger">' + escapeHtml(row.errors.join(' ')) + '</span>';
                } else {
                    statusHtml = '<span class="badge badge-secondary">Ready</span>';
                }

                var cells = '<td>' + escapeHtml(row.row) + '</td>';
                columns.forEach(function (column) {
                    var alignClass = column.align === 'right' ? ' class="text-right"' : '';
                    cells += '<td' + alignClass + '>' + escapeHtml(row[column.key]) + '</td>';
                });
                cells += '<td>' + statusHtml + '</td>';

                $body.append('<tr' + (hasErrors ? ' class="table-danger"' : '') + '>' + cells + '</tr>');
            });

            return { validCount: validCount, savedCount: savedCount, total: rows.length };
        }

        function ajaxErrorMessage(xhr) {
            if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                return xhr.responseJSON.message || xhr.responseJSON.error;
            }
            return 'Something went wrong. Please check the file and try again.';
        }

        function runPreview($modal, file) {
            $modal.find('.excel-import-error').addClass('d-none').text('');
            $modal.find('.excel-import-summary').addClass('d-none').text('');
            $modal.find('.excel-import-table-wrapper').hide();
            $modal.find('.excel-import-table-body').empty();
            $modal.find('.excel-import-save-btn').addClass('d-none').prop('disabled', true);
            $modal.find('.excel-import-loading').removeClass('d-none');

            var formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: $modal.data('preview-url'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (response) {
                    $modal.find('.excel-import-loading').addClass('d-none');
                    if (response.error) {
                        $modal.find('.excel-import-error').removeClass('d-none').text(response.error);
                        return;
                    }
                    if (!response.rows || response.rows.length === 0) {
                        $modal.find('.excel-import-error').removeClass('d-none').text('No data rows found in the file.');
                        return;
                    }
                    var counts = renderRows($modal, response.rows);
                    $modal.find('.excel-import-table-wrapper').show();
                    $modal.find('.excel-import-summary').removeClass('d-none').text(
                        counts.total + ' row(s) found - ' + counts.validCount + ' ready to import, ' + (counts.total - counts.validCount) + ' with errors.'
                    );
                    if (counts.validCount > 0) {
                        $modal.find('.excel-import-save-btn').removeClass('d-none').prop('disabled', false);
                    }
                },
                error: function (xhr) {
                    $modal.find('.excel-import-loading').addClass('d-none');
                    $modal.find('.excel-import-error').removeClass('d-none').text(ajaxErrorMessage(xhr));
                },
            });
        }

        function runSave($modal, $btn, file) {
            $btn.prop('disabled', true).text('Saving...');
            $modal.find('.excel-import-error').addClass('d-none').text('');
            $modal.find('.excel-import-loading').removeClass('d-none');

            var formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: $modal.data('save-url'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (response) {
                    $modal.find('.excel-import-loading').addClass('d-none');
                    if (response.error) {
                        $modal.find('.excel-import-error').removeClass('d-none').text(response.error);
                        $btn.prop('disabled', false).text('Save Valid Rows');
                        return;
                    }
                    var counts = renderRows($modal, response.rows);
                    $modal.find('.excel-import-summary').removeClass('d-none').removeClass('alert-info').addClass('alert-success').text(
                        counts.savedCount + ' row(s) saved successfully. ' + (counts.total - counts.savedCount) + ' row(s) skipped due to errors.'
                    );

                    if (counts.savedCount > 0) {
                        $btn.addClass('d-none');
                        setTimeout(function () { window.location.reload(); }, 1500);
                    } else {
                        $btn.removeClass('d-none').prop('disabled', false).text('Save Valid Rows');
                    }
                },
                error: function (xhr) {
                    $modal.find('.excel-import-loading').addClass('d-none');
                    $btn.prop('disabled', false).text('Save Valid Rows');
                    $modal.find('.excel-import-error').removeClass('d-none').text(ajaxErrorMessage(xhr));
                },
            });
        }

        $(document).on('change', '.excel-import-file', function () {
            var file = this.files && this.files[0];
            if (!file) { return; }
            var $modal = $(this).closest('.modal');
            $modal.find('.excel-import-save-btn').data('selectedFile', file);
            runPreview($modal, file);
        });

        $(document).on('click', '.excel-import-save-btn', function () {
            var $btn = $(this);
            var file = $btn.data('selectedFile');
            if (!file) { return; }
            runSave($btn.closest('.modal'), $btn, file);
        });

        $(document).on('hidden.bs.modal', '.excel-import-modal', function () {
            resetModal($(this));
        });
    })();
</script>
@endonce
@endpush
