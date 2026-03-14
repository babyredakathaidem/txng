<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    private string $gatewayUrl;
    private bool   $isMock;

    public function __construct()
    {
        $this->gatewayUrl = config('services.fabric.gateway_url', '');
        $this->isMock     = empty($this->gatewayUrl)
            || str_starts_with($this->gatewayUrl, 'mock://')
            || config('services.fabric.mock', false);
    }

    // ──────────────────────────────────────────────────────
    // WRITE — ghi sự kiện truy xuất lên Hyperledger Fabric
    // ──────────────────────────────────────────────────────

    public function recordEvent(
        string $eventID,
        string $batchCode,
        string $enterpriseID,
        string $cteCode,
        string $contentHash,
        string $ipfsCid,
        string $recordedBy
    ): array {
        if ($this->isMock) {
            Log::info('[Blockchain MOCK] recordEvent', ['eventID' => $eventID]);
            return ['success' => true, 'data' => ['txId' => 'mock-tx-' . substr($contentHash, 0, 16)]];
        }

        try {
            $response = Http::timeout(30)->post("{$this->gatewayUrl}/fabric/record-event", [
                'eventID'      => $eventID,
                'batchCode'    => $batchCode,
                'enterpriseID' => $enterpriseID,
                'cteCode'      => $cteCode,
                'contentHash'  => $contentHash,
                'ipfsCid'      => $ipfsCid,
                'recordedBy'   => $recordedBy,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::warning('[Blockchain] recordEvent failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'error' => $response->json('error')];

        } catch (\Exception $e) {
            Log::error('[Blockchain] recordEvent exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────
    // READ — đọc record từ Fabric để verify
    //
    // Trả về array gồm:
    //   found        bool    — có record trên Fabric không
    //   mock         bool    — đang chạy mock mode
    //   content_hash string  — hash đã ghi lên Fabric lúc publish
    //   ipfs_cid     string  — CID đã ghi lên Fabric lúc publish
    //   recorded_by  string
    //   timestamp    string
    // ──────────────────────────────────────────────────────

    public function getEventRecord(string $eventID): array
    {
        // ── MOCK MODE ─────────────────────────────────────
        // Khi không có Fabric gateway thật, mock trả về
        // found=false để caller biết dùng DB làm fallback
        if ($this->isMock) {
            Log::info('[Blockchain MOCK] getEventRecord — mock mode, no real Fabric', ['eventID' => $eventID]);
            return [
                'found'        => false,
                'mock'         => true,
                'content_hash' => null,
                'ipfs_cid'     => null,
                'recorded_by'  => null,
                'timestamp'    => null,
            ];
        }

        // ── REAL MODE ─────────────────────────────────────
        try {
            $response = Http::timeout(10)->get("{$this->gatewayUrl}/fabric/event/{$eventID}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'found'        => true,
                    'mock'         => false,
                    'content_hash' => $data['contentHash']   ?? $data['content_hash']   ?? null,
                    'ipfs_cid'     => $data['ipfsCid']       ?? $data['ipfs_cid']       ?? null,
                    'recorded_by'  => $data['recordedBy']    ?? $data['recorded_by']    ?? null,
                    'timestamp'    => $data['timestamp']     ?? null,
                ];
            }

            if ($response->status() === 404) {
                return ['found' => false, 'mock' => false, 'content_hash' => null, 'ipfs_cid' => null, 'recorded_by' => null, 'timestamp' => null];
            }

            Log::warning('[Blockchain] getEventRecord non-200', [
                'eventID' => $eventID,
                'status'  => $response->status(),
            ]);

            return ['found' => false, 'mock' => false, 'content_hash' => null, 'ipfs_cid' => null, 'recorded_by' => null, 'timestamp' => null];

        } catch (\Exception $e) {
            Log::error('[Blockchain] getEventRecord exception', [
                'eventID' => $eventID,
                'message' => $e->getMessage(),
            ]);
            return ['found' => false, 'mock' => false, 'content_hash' => null, 'ipfs_cid' => null, 'recorded_by' => null, 'timestamp' => null];
        }
    }

    // ──────────────────────────────────────────────────────
    // TRANSFER — chuyển giao lô giữa các doanh nghiệp
    // ──────────────────────────────────────────────────────

    public function transferBatch(
        string $transferID,
        string $batchCode,
        string $fromEnterprise,
        string $toEnterprise,
        string $invoiceNo = ''
    ): array {
        if ($this->isMock) {
            Log::info('[Blockchain MOCK] transferBatch', ['transferID' => $transferID]);
            return ['success' => true, 'data' => ['txId' => 'mock-transfer-' . substr(md5($transferID), 0, 12)]];
        }

        try {
            $response = Http::timeout(30)->post("{$this->gatewayUrl}/fabric/transfer-batch", [
                'transferID'     => $transferID,
                'batchCode'      => $batchCode,
                'fromEnterprise' => $fromEnterprise,
                'toEnterprise'   => $toEnterprise,
                'invoiceNo'      => $invoiceNo,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json('error')];

        } catch (\Exception $e) {
            Log::error('[Blockchain] transferBatch error', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    public function verifyContentHash(string $eventID, string $expectedHash): bool
    {
        $record = $this->getEventRecord($eventID);
        if (!$record['found']) return false;
        return hash_equals($expectedHash, $record['content_hash'] ?? '');
    }

    public function isMockMode(): bool
    {
        return $this->isMock;
    }
}