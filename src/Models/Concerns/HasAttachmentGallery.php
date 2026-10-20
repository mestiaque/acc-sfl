<?php

namespace ME\AccSfl\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * One list of a record's files for the attachment gallery (admin/partials/attachment-gallery):
 * the legacy single `attachment` column, if the model has one, followed by its `attachments()`
 * rows in the central files table. Each entry: ['url', 'name', 'image' => bool].
 */
trait HasAttachmentGallery
{
    public function galleryFiles(): array
    {
        $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $files = [];

        if (! empty($this->attachment)) {
            $files[] = [
                'url' => Storage::disk('public')->url($this->attachment),
                'name' => basename($this->attachment),
                'image' => in_array(strtolower(pathinfo($this->attachment, PATHINFO_EXTENSION)), $imageExt, true),
            ];
        }

        foreach ($this->attachments as $file) {
            $files[] = [
                'url' => $file->file_url,
                'name' => $file->original_name ?: $file->file_name,
                'image' => str_starts_with((string) $file->file_type, 'image/')
                    || in_array(strtolower((string) $file->extension), $imageExt, true),
            ];
        }

        return $files;
    }
}
