{{--
    Styles, lightbox modal and behaviour for every .ac-gallery on the page (see
    attachment-gallery.blade.php, or build one in JS with acGalleryHtml(files)). Galleries added
    later - e.g. the AJAX-loaded expense details - are rendered automatically.
--}}
<style>
    .ac-gallery-stage { border: 1px solid #dee2e6; border-radius: 4px; background: #f8f9fa; height: 300px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .ac-gallery-stage img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: zoom-in; }
    .ac-gallery-file { text-align: center; color: #6c757d; padding: 12px; word-break: break-all; }
    .ac-gallery-bar { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 6px; }
    .ac-gallery-name { font-size: 12px; color: #6c757d; margin-top: 4px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #acAttachmentLightbox { z-index: 1065; }
    #acAttachmentLightbox .modal-body { background: #111; display: flex; align-items: center; justify-content: center; min-height: 70vh; }
    #acAttachmentLightbox img { max-width: 100%; max-height: 80vh; object-fit: contain; }
    #acAttachmentLightbox iframe { width: 100%; height: 80vh; border: 0; background: #fff; }
</style>

<div class="modal fade" id="acAttachmentLightbox" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-truncate" id="acLightboxTitle"></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-2" id="acLightboxBody"></div>
            <div class="modal-footer py-2 justify-content-between">
                <button type="button" class="btn btn-light btn-sm" id="acLightboxPrev"><i class="fa-solid fa-chevron-left mr-1"></i> Prev</button>
                <span class="small text-muted" id="acLightboxCounter"></span>
                <div>
                    <a href="#" target="_blank" class="btn btn-light btn-sm" id="acLightboxOpen"><i class="fa-solid fa-up-right-from-square mr-1"></i> Open</a>
                    <button type="button" class="btn btn-light btn-sm" id="acLightboxNext">Next <i class="fa-solid fa-chevron-right ml-1"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function ($) {
        function esc(text) { return $('<div>').text(text == null ? '' : String(text)).html(); }
        function isPdf(file) { return /\.pdf($|\?)/i.test(file.name || '') || /\.pdf($|\?)/i.test(file.url || ''); }

        window.acGalleryHtml = function (files) {
            return '<div class="ac-gallery" data-files="' + esc(JSON.stringify(files || [])) + '"></div>';
        };

        function stageHtml(file) {
            if (file.image) {
                return '<img src="' + esc(file.url) + '" alt="' + esc(file.name) + '" class="ac-gallery-zoom">';
            }
            var icon = isPdf(file) ? 'fa-file-pdf text-danger' : 'fa-file';
            return '<div class="ac-gallery-file"><i class="fa-solid ' + icon + ' fa-3x mb-2"></i><br>' + esc(file.name) + '</div>';
        }

        function render($gallery) {
            var files = $gallery.data('files') || [];
            var index = $gallery.data('index') || 0;
            if (!files.length) {
                $gallery.html('<div class="ac-gallery-stage text-muted" style="height:160px;"><span><i class="fa-regular fa-image mr-1"></i> No attachment</span></div>');
                return;
            }
            var file = files[index];
            $gallery.html(
                '<div class="ac-gallery-stage">' + stageHtml(file) + '</div>' +
                '<div class="ac-gallery-name" title="' + esc(file.name) + '">' + esc(file.name) + '</div>' +
                '<div class="ac-gallery-bar">' +
                    '<button type="button" class="btn btn-light btn-sm ac-gallery-prev"' + (files.length < 2 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-left"></i> Prev</button>' +
                    '<span class="small text-muted">' + (index + 1) + ' / ' + files.length + '</span>' +
                    '<button type="button" class="btn btn-primary btn-sm ac-gallery-view"><i class="fa-solid fa-eye mr-1"></i> View</button>' +
                    '<button type="button" class="btn btn-light btn-sm ac-gallery-next"' + (files.length < 2 ? ' disabled' : '') + '>Next <i class="fa-solid fa-chevron-right"></i></button>' +
                '</div>'
            );
        }

        function step($gallery, delta) {
            var files = $gallery.data('files') || [];
            if (files.length < 2) { return; }
            $gallery.data('index', ((($gallery.data('index') || 0) + delta) % files.length + files.length) % files.length);
            render($gallery);
        }

        window.acInitGalleries = function (root) {
            $(root || document).find('.ac-gallery').each(function () {
                var $gallery = $(this);
                if ($gallery.data('acReady')) { return; }
                $gallery.data('acReady', true).data('index', 0);
                render($gallery);
            });
        };

        // Lightbox: shows the same file list large, with its own Prev / Next.
        var lightbox = { files: [], index: 0 };

        function renderLightbox() {
            var file = lightbox.files[lightbox.index];
            $('#acLightboxTitle').text(file.name);
            $('#acLightboxOpen').attr('href', file.url);
            $('#acLightboxCounter').text((lightbox.index + 1) + ' / ' + lightbox.files.length);
            $('#acLightboxPrev, #acLightboxNext').prop('disabled', lightbox.files.length < 2);
            $('#acLightboxBody').html(file.image
                ? '<img src="' + esc(file.url) + '" alt="' + esc(file.name) + '">'
                : (isPdf(file)
                    ? '<iframe src="' + esc(file.url) + '"></iframe>'
                    : '<div class="text-white text-center"><i class="fa-solid fa-file fa-3x mb-2"></i><br>' + esc(file.name) + '<br><small>Preview not available - use Open.</small></div>'));
        }

        function openLightbox($gallery) {
            lightbox.files = $gallery.data('files') || [];
            lightbox.index = $gallery.data('index') || 0;
            if (!lightbox.files.length) { return; }
            renderLightbox();
            $('#acAttachmentLightbox').modal('show');
        }

        $(document).on('click', '.ac-gallery-prev', function () { step($(this).closest('.ac-gallery'), -1); });
        $(document).on('click', '.ac-gallery-next', function () { step($(this).closest('.ac-gallery'), 1); });
        $(document).on('click', '.ac-gallery-view, .ac-gallery-zoom', function () { openLightbox($(this).closest('.ac-gallery')); });
        $(document).on('click', '#acLightboxPrev, #acLightboxNext', function () {
            if (lightbox.files.length < 2) { return; }
            var delta = this.id === 'acLightboxPrev' ? -1 : 1;
            lightbox.index = ((lightbox.index + delta) % lightbox.files.length + lightbox.files.length) % lightbox.files.length;
            renderLightbox();
        });

        // Opened over another modal (e.g. Expense Details): keep its backdrop above that one,
        // and keep the page scroll-locked while the modal underneath is still open.
        $('#acAttachmentLightbox').on('shown.bs.modal', function () {
            $('.modal-backdrop').last().css('z-index', 1060);
        }).on('hidden.bs.modal', function () {
            $('#acLightboxBody').empty();
            if ($('.modal.show').length) { $('body').addClass('modal-open'); }
        });

        $(function () {
            $('#acAttachmentLightbox').appendTo('body');
            acInitGalleries(document);
            // Render galleries that arrive later (AJAX-loaded details, JS-built view modals).
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    $(mutation.addedNodes).each(function () {
                        if (this.nodeType === 1) { acInitGalleries($(this).is('.ac-gallery') ? $(this).parent() : this); }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        });
    })(jQuery);
</script>
