<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpfsService
{
    private string $apiKey;
    private string $secretKey;
    private string $gateway;
    private bool   $isMock;

    public function __construct()
    {
        $this->apiKey    = config('services.pinata.api_key', '');
        $this->secretKey = config('services.pinata.secret_key', '');
        $this->gateway   = config('services.pinata.gateway', 'https://gateway.pinata.cloud');
        $this->isMock    = empty($this->apiKey) || str_starts_with($this->apiKey, 'your_');
    }

    // ── Upload JSON ───────────────────────────────────────

    /**
     * Upload JSON payload lên IPFS.
     * @return array{cid: string, url: string, mock: bool}|null
     */
    public function uploadJson(array $data, string $name = 'trace-event'): ?array
    {
        if ($this->isMock) {
            return $this->mockJson($data);
        }

        try {
            $response = Http::withHeaders([
                'pinata_api_key'        => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])->timeout(30)->post('https://api.pinata.cloud/pinning/pinJSONToIPFS', [
                'pinataContent'  => $data,
                'pinataMetadata' => ['name' => $name],
                'pinataOptions'  => ['cidVersion' => 1],
            ]);

            if ($response->successful()) {
                $cid = $response->json('IpfsHash');
                return ['cid' => $cid, 'url' => "{$this->gateway}/ipfs/{$cid}", 'mock' => false];
            }

            Log::error('[IPFS] Pinata JSON upload failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[IPFS] JSON upload exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    // ── Upload File ───────────────────────────────────────

    /**
     * Upload file (ảnh, PDF...) lên IPFS qua Pinata.
     * $fileContent = nội dung file (string)
     * $filename    = tên file gốc
     * $mimeType    = MIME type
     *
     * @return array{cid: string, url: string, mock: bool}|null
     */
    public function uploadFile(string $fileContent, string $filename, string $mimeType): ?array
    {
        if ($this->isMock) {
            return $this->mockFile($fileContent, $filename);
        }

        try {
            $response = Http::withHeaders([
                'pinata_api_key'        => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])->timeout(60)->attach(
                'file',
                $fileContent,
                $filename,
                ['Content-Type' => $mimeType]
            )->post('https://api.pinata.cloud/pinning/pinFileToIPFS', [
                'pinataMetadata' => json_encode(['name' => $filename]),
                'pinataOptions'  => json_encode(['cidVersion' => 1]),
            ]);

            if ($response->successful()) {
                $cid = $response->json('IpfsHash');
                return [
                    'cid'  => $cid,
                    'url'  => "{$this->gateway}/ipfs/{$cid}",
                    'mock' => false,
                ];
            }

            Log::error('[IPFS] Pinata file upload failed', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::error('[IPFS] File upload exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    // ── Verify ────────────────────────────────────────────

    /**
     * Xác minh tính toàn vẹn: fetch CID từ IPFS gateway, re-hash và so sánh.
     *
     * @return array{valid: bool, fetched: bool, expected_hash: string, actual_hash: string|null, mock: bool}
     */
    public function verify(string $cid, string $expectedHash): array
    {
        $base = ['expected_hash' => $expectedHash, 'actual_hash' => null, 'mock' => $this->isMock];

        if ($this->isMock) {
            // Mock: giả sử luôn hợp lệ (dùng cho demo)
            return array_merge($base, ['valid' => true, 'fetched' => true]);
        }

        try {
            $url      = "{$this->gateway}/ipfs/{$cid}";
            $response = Http::timeout(20)->get($url);

            if (!$response->successful()) {
                return array_merge($base, ['valid' => false, 'fetched' => false]);
            }

            $body       = $response->body();
            $actualHash = hash('sha256', $body);

            return array_merge($base, [
                'valid'       => hash_equals($expectedHash, $actualHash),
                'fetched'     => true,
                'actual_hash' => $actualHash,
            ]);
        } catch (\Throwable $e) {
            Log::error('[IPFS] Verify exception', ['message' => $e->getMessage()]);
            return array_merge($base, ['valid' => false, 'fetched' => false]);
        }
    }

    // ── Mock helpers ──────────────────────────────────────

    private function mockJson(array $data): array
    {
        $json    = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fakeCid = 'bafyMOCK' . substr(hash('sha256', $json), 0, 46);
        return ['cid' => $fakeCid, 'url' => "{$this->gateway}/ipfs/{$fakeCid}", 'mock' => true];
    }

    private function mockFile(string $content, string $filename): array
    {
        $fakeCid = 'bafyFILE' . substr(hash('sha256', $content . $filename), 0, 46);
        return ['cid' => $fakeCid, 'url' => "{$this->gateway}/ipfs/{$fakeCid}", 'mock' => true];
    }
}