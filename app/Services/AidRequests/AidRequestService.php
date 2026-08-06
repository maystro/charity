<?php

namespace App\Services\AidRequests;

use App\Enums\AidRequestStatus;
use App\Events\AidRequestSubmitted;
use App\Models\AidRequest;
use App\Models\AidRequestStatusHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AidRequestService
{
    public function __construct(
        private readonly AidRequestNumberGenerator $numberGenerator
    ) {}

    /**
     * Create a new draft or update an existing one.
     * Partial data is accepted; items array is synced if provided.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdateDraft(array $data, ?int $id = null): AidRequest
    {
        return DB::transaction(function () use ($data, $id) {
            $items = $data['items'] ?? null;
            $attachments = $data['attachments'] ?? null;
            unset($data['items'], $data['attachments']);

            $aidRequest = $id ? AidRequest::findOrFail($id) : new AidRequest;

            if (! $aidRequest->exists) {
                $data['request_number'] = $this->numberGenerator->generate();
                $data['created_by'] = Auth::id();
                $data['requested_at'] = $data['requested_at'] ?? now()->toDateString();
                $data['source_type'] = $data['source_type'] ?? 'الأسرة مباشرة';
            }

            $data['status'] = AidRequestStatus::Draft->value;

            $aidRequest->fill($data);
            $aidRequest->save();

            if (is_array($items)) {
                $this->syncItems($aidRequest, $items);
            }

            if (is_array($attachments)) {
                $this->syncAttachments($aidRequest, $attachments);
            }

            return $aidRequest;
        });
    }

    /**
     * Submit a draft for review.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, ?int $id = null): AidRequest
    {
        return DB::transaction(function () use ($data, $id) {
            $items = $data['items'] ?? null;
            $attachments = $data['attachments'] ?? null;
            unset($data['items'], $data['attachments']);

            $aidRequest = $id ? AidRequest::findOrFail($id) : new AidRequest;

            if (! $aidRequest->exists) {
                $data['request_number'] = $this->numberGenerator->generate();
                $data['created_by'] = Auth::id();
                $data['requested_at'] = $data['requested_at'] ?? now()->toDateString();
                $data['source_type'] = $data['source_type'] ?? 'الأسرة مباشرة';
            }

            $fromStatus = $aidRequest->status;

            $data['status'] = AidRequestStatus::Submitted->value;
            $data['submitted_at'] = now();
            $data['submitted_by'] = Auth::id();

            $aidRequest->fill($data);
            $aidRequest->save();

            if (is_array($items)) {
                $this->syncItems($aidRequest, $items);
            }

            if (is_array($attachments)) {
                $this->syncAttachments($aidRequest, $attachments);
            }

            // Record status transition
            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => AidRequestStatus::Submitted->value,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);

            event(new AidRequestSubmitted($aidRequest));

            return $aidRequest;
        });
    }

    /**
     * Sync items for an aid request (delete existing and re-insert).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(AidRequest $aidRequest, array $items): void
    {
        $aidRequest->items()->delete();

        $total = '0';

        foreach ($items as $index => $item) {
            $quantity = (string) ($item['quantity'] ?? 1);
            $unitCost = (string) ($item['unit_cost'] ?? 0);
            $estimated = bcmul($quantity, $unitCost, 2);
            $total = bcadd($total, $estimated, 2);

            $itemRecord = $aidRequest->items()->create([
                'aid_type' => $item['aid_type'] ?? null,
                'category_id' => $item['category_id'] ?? 1,
                'title' => $item['title'] ?? '',
                'description' => $item['description'] ?? null,
                'execution_type' => $item['execution_type'] ?? 'وقتية',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'estimated_total' => $estimated,
                'recurrence_type' => $item['recurrence_type'] ?? 'وقتية',
                'recurrence_interval_days' => $item['recurrence_interval_days'] ?? null,
                'recurrence_start' => $item['execution_start_date'] ?? $item['recurrence_start'] ?? null,
                'execution_start_date' => $item['execution_start_date'] ?? null,
                'priority' => $item['priority'] ?? 'عادية',
                'sort_order' => $index,
            ]);

            // إرفاق ملفات على مستوى البند
            $attachments = $item['attachments'] ?? [];
            if (! empty($attachments)) {
                $this->syncItemAttachments($aidRequest, $itemRecord, $attachments);
            }
        }

        $aidRequest->update(['total_estimated_amount' => $total]);
    }

    /**
     * Persist uploaded attachments directly linked to an aid request item.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function syncItemAttachments(AidRequest $aidRequest, $item, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $file = $attachment['file'] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            // Capture metadata before storeAs() moves the file from livewire-tmp
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $storedName = $file->hashName();

            $path = $file->storeAs('aid-requests/'.$aidRequest->id, $storedName, 'local');

            $aidRequest->attachments()->create([
                'aid_request_item_id' => $item->id,
                'attachment_type_id' => 1,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $mimeType,
                'size' => $size,
                'notes' => $attachment['name'] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Persist uploaded attachments for an aid request.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function syncAttachments(AidRequest $aidRequest, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $file = $attachment['file'] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            // Capture metadata before storeAs() moves the file from livewire-tmp
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $storedName = $file->hashName();

            $path = $file->storeAs('aid-requests/'.$aidRequest->id, $storedName, 'local');

            $aidRequest->attachments()->create([
                'attachment_type_id' => 1,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $mimeType,
                'size' => $size,
                'notes' => $attachment['type'] ?? $attachment['name'] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
        }
    }
}
