<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Services\UploadService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;

    /**
     * Create a new job instance.
     */
    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle(UploadService $uploadService): void
    {
        $upload = Upload::find($this->uploadId);
        if (!$upload) {
            return;
        }

        $upload->update(['status' => 'pending']);

        try {
            $result = $uploadService->processUpload($upload->stored_filepath);

            $upload->update([
                'status' => 'completed',
                'xml_filepath' => $result['file_name'],
                'response' => json_encode($result),
            ]);
        } catch (Exception $e) {
            $upload->update([
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
