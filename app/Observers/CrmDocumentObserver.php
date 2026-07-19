<?php

namespace App\Observers;

use App\Models\CrmDocument;
use Illuminate\Support\Facades\Storage;

class CrmDocumentObserver
{
    public function deleted(CrmDocument $document): void
    {
        if ($document->disk && $document->disk_path) {
            Storage::disk($document->disk)->delete($document->disk_path);
        }
    }
}
