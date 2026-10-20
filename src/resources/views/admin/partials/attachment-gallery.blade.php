{{--
    Attachment gallery placeholder. $files = $model->galleryFiles(). Rendered client-side by
    attachment-gallery-assets (include that once per page): one file at a time with
    Prev / Next / View below it; View opens the file large in a lightbox modal.
--}}
<div class="ac-gallery" data-files='@json($files ?? [])'></div>
