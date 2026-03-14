<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Qrcode;
use Illuminate\Support\Str;

/**
 * QrCodeService — Nâng cấp GS1 Digital Link
 *
 * Chuẩn áp dụng: GS1 Digital Link Standard v1.2
 * URI format: https://{domain}/01/{gtin}/10/{lot_number}
 *
 * AI = 01 → GTIN-14 (Global Trade Item Number)
 * AI = 10 → Lot/Batch Number
 *
 * Tham chiếu: https://www.gs1.org/standards/gs1-digital-link
 */
class QrCodeService
{
    /**
     * Tạo hoặc đảm bảo QR code tồn tại cho một lô hàng.
     * Tự động sinh GS1 Digital Link URI.
     */
    public function ensureForBatch(int $enterpriseId, int $batchId): void
    {
        $batch = Batch::with('product:id,gtin,name,enterprise_id')
            ->find($batchId);

        if (! $batch) {
            return;
        }

        $gtin    = $this->resolveGtin($batch);
        $lotCode = $this->normalizeLotCode($batch->code);

        foreach (['public', 'private'] as $type) {
            $gs1Url = $this->buildGs1DigitalLink($gtin, $lotCode, $type, $batch);

            Qrcode::firstOrCreate(
                ['batch_id' => $batchId, 'type' => $type],
                [
                    'enterprise_id'    => $enterpriseId,
                    'token'            => Str::random(60),
                    'gs1_digital_link' => $gs1Url,
                    'gtin_used'        => $gtin,
                ]
            );
        }
    }

    /**
     * Tái sinh GS1 Digital Link cho QR đã tồn tại (khi GTIN được cập nhật sau).
     */
    public function regenerateGs1Link(Qrcode $qr): void
    {
        $batch = Batch::with('product:id,gtin,name,enterprise_id')
            ->find($qr->batch_id);

        if (! $batch) {
            return;
        }

        $gtin    = $this->resolveGtin($batch);
        $lotCode = $this->normalizeLotCode($batch->code);
        $gs1Url  = $this->buildGs1DigitalLink($gtin, $lotCode, $qr->type, $batch);

        $qr->update([
            'gs1_digital_link' => $gs1Url,
            'gtin_used'        => $gtin,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Xây dựng GS1 Digital Link URI hoàn chỉnh.
     *
     * Public QR → trỏ thẳng tới resolver (người dùng cuối quét)
     *   https://domain/01/{gtin}/10/{lot}
     *
     * Private QR → vẫn dùng token vì cần xác thực 48h
     *   https://domain/v/{token}  (không phải GS1 DL thuần túy)
     *   Nhưng ta vẫn lưu GS1 DL uri vào gs1_digital_link để reference.
     *
     * Để public QR hoàn toàn chuẩn GS1 DL, route resolver phải là:
     *   GET /01/{gtin}/10/{lot} → QrScanController@resolveGs1Public
     */
    private function buildGs1DigitalLink(
        string $gtin,
        string $lotCode,
        string $type,
        Batch  $batch
    ): string {
        $base = rtrim(config('app.url'), '/');

        if ($type === 'public') {
            // Chuẩn GS1 Digital Link thuần túy
            // Resolver tại route: GET /01/{gtin}/10/{lot}
            return "{$base}/01/{$gtin}/10/" . urlencode($lotCode);
        }

        // Private QR: GS1 DL dạng mở rộng với custom qualifier
        // Theo GS1 DL spec, có thể thêm custom path sau primary key
        // Ở đây ta dùng /private/ để phân biệt và vẫn embed lot
        // Token được thêm vào query string (không chuẩn GS1 hoàn toàn nhưng acceptable)
        return "{$base}/01/{$gtin}/10/" . urlencode($lotCode) . "?type=private&eid={$batch->enterprise_id}";
    }

    /**
     * Lấy GTIN-14 hợp lệ cho sản phẩm.
     *
     * Ưu tiên: GTIN thật của product → sinh GTIN nội bộ từ enterprise_id + product_id
     * Tất cả đều pad về 14 số với check digit đúng.
     */
    public function resolveGtin(Batch $batch): string
    {
        $rawGtin = $batch->product?->gtin ?? null;

        if ($rawGtin && preg_match('/^\d{8,14}$/', $rawGtin)) {
            // Pad về 14 chữ số (GTIN-14)
            $padded = str_pad($rawGtin, 14, '0', STR_PAD_LEFT);
            return $padded;
        }

        // Không có GTIN thật → sinh mã nội bộ (Internal GTIN)
        // Prefix 200 = GS1 prefix dành riêng cho mã nội bộ (restricted circulation)
        // Format: 200 + enterprise_id(5) + product_id(5) + check_digit(1) = 14 digits
        $enterpriseId = str_pad($batch->enterprise_id, 5, '0', STR_PAD_LEFT);
        $productId    = str_pad($batch->product_id ?? 0, 5, '0', STR_PAD_LEFT);
        $body         = '200' . $enterpriseId . $productId; // 13 digits
        $check        = $this->calculateCheckDigit($body);

        return $body . $check;
    }

    /**
     * Tính check digit GS1 (Modulo-10 / Luhn variant).
     * Áp dụng cho GTIN-8, GTIN-12, GTIN-13, GTIN-14.
     *
     * @param string $digits 13 chữ số (không có check digit)
     */
    public function calculateCheckDigit(string $digits): string
    {
        $sum = 0;
        $len = strlen($digits);

        for ($i = 0; $i < $len; $i++) {
            $d = (int) $digits[$i];
            // Vị trí từ phải: chẵn × 3, lẻ × 1
            $sum += ($len - $i) % 2 === 0 ? $d * 3 : $d;
        }

        $check = (10 - ($sum % 10)) % 10;
        return (string) $check;
    }

    /**
     * Chuẩn hóa mã lô để dùng trong GS1 Digital Link.
     *
     * GS1 AI 10 (Lot/Batch Number): tối đa 20 ký tự, charset GS1-82
     * (A-Z, a-z, 0-9 và một số ký tự đặc biệt).
     */
    private function normalizeLotCode(string $code): string
    {
        // Loại bỏ ký tự không hợp lệ trong GS1 charset
        $normalized = preg_replace('/[^A-Za-z0-9\-_.]/', '-', $code);
        // Giới hạn 20 ký tự
        return substr($normalized, 0, 20);
    }
}