<?php

namespace App\Jobs;

use App\Models\TraceEvent;
use App\Services\BlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFabricPublishJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần thử lại tối đa.
     */
    public $tries = 3;

    /**
     * Thời gian chờ giữa các lần retry (tính bằng giây).
     * 300 giây = 5 phút
     */
    public $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int    $traceEventId,
        public string $batchCode,
        public string $enterpriseCode,
        public string $cteCode,
        public string $contentHash,
        public string $ipfsCid,
        public string $recordedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BlockchainService $blockchainService): void
    {
        $event = TraceEvent::find($this->traceEventId);

        if (!$event) {
            Log::warning('[RetryFabric] Event không tồn tại', ['event_id' => $this->traceEventId]);
            return;
        }

        // Nếu đã có tx_hash thì thôi không ghi lại
        if (!empty($event->tx_hash)) {
            Log::info('[RetryFabric] Event đã có tx_hash, skip', ['event_id' => $this->traceEventId]);
            return;
        }

        try {
            $result = $blockchainService->recordEvent(
                eventID:      (string) $this->traceEventId,
                batchCode:    $this->batchCode,
                enterpriseID: $this->enterpriseCode,
                cteCode:      $this->cteCode,
                contentHash:  $this->contentHash,
                ipfsCid:      $this->ipfsCid,
                recordedBy:   $this->recordedBy,
            );

            if ($result['success']) {
                $txHash = $result['data']['txId']
                    ?? $result['data']['tx_hash']
                    ?? $result['data']['transactionId']
                    ?? null;

                $event->update(['tx_hash' => $txHash]);

                Log::info('[RetryFabric] Publish thành công qua Job', [
                    'event_id' => $this->traceEventId,
                    'tx_hash'  => $txHash,
                ]);
            } else {
                // Quăng exception để Laravel đẩy job vào fail queue và retry sau
                throw new \Exception('Fabric gateway returned failure: ' . ($result['error'] ?? 'unknown error'));
            }

        } catch (\Throwable $e) {
            Log::error('[RetryFabric] Lỗi khi gọi Fabric, chuẩn bị retry...', [
                'event_id' => $this->traceEventId,
                'error'    => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Xử lý khi job hoàn toàn thất bại (sau số lần tries).
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('[RetryFabric] Job thất bại hoàn toàn sau 3 lần thử', [
            'event_id' => $this->traceEventId,
            'error'    => $exception->getMessage(),
        ]);

        // TODO: Có thể notify cho Admin hoặc System alert service tại đây
    }
}
