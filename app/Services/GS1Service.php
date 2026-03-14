<?php

namespace App\Services;

/**
 * GS1Service — Tiện ích định danh theo chuẩn GS1 / TCVN 12850:2019
 *
 * Các định danh hỗ trợ:
 *  - GTIN  (Global Trade Item Number): 8, 12, 13, 14 chữ số
 *  - GLN   (Global Location Number):   13 chữ số, prefix Vietnam = 893
 *  - SGTIN (Serialised GTIN):          GTIN + serial/lot, dùng trong EPCIS
 *  - GS1-128 AI mapping cho lô hàng:   AI(01) GTIN + AI(10) Lot + AI(17) BBD
 *
 * Tham chiếu: GS1 General Specifications v24, TCVN 12850:2019
 */
class GS1Service
{
    // GS1 Company Prefix của hệ thống AGU (dùng cho GLN nội bộ)
    // Thực tế doanh nghiệp phải đăng ký với GS1 Việt Nam (893...)
    // Ở đây dùng 8930000 làm demo prefix cho luận văn
    private const SYSTEM_GLN_PREFIX = '8930000'; // 7 chữ số

    // ─────────────────────────────────────────────────────────────
    // GTIN
    // ─────────────────────────────────────────────────────────────

    /**
     * Kiểm tra GTIN hợp lệ theo thuật toán GS1 check digit.
     * Hỗ trợ GTIN-8, GTIN-12, GTIN-13, GTIN-14.
     *
     * Thuật toán: nhân xen kẽ 3-1 từ phải qua trái (trừ check digit cuối),
     * tổng + check digit ≡ 0 (mod 10).
     */
    public function validateGTIN(string $gtin): bool
    {
        $gtin = preg_replace('/\D/', '', $gtin);

        if (!in_array(strlen($gtin), [8, 12, 13, 14])) {
            return false;
        }

        return $this->gs1CheckDigit(substr($gtin, 0, -1), strlen($gtin)) === (int) substr($gtin, -1);
    }

    /**
     * Tính check digit GS1 cho chuỗi số (chưa có check digit).
     *
     * @param string $digits  Chuỗi số chưa có check digit
     * @param int    $totalLen Tổng độ dài sau khi thêm check digit (8, 12, 13, 14)
     */
    public function gs1CheckDigit(string $digits, int $totalLen = 13): int
    {
        // Pad trái về độ dài totalLen - 1
        $digits = str_pad($digits, $totalLen - 1, '0', STR_PAD_LEFT);
        $sum    = 0;

        // Từ phải qua trái: vị trí cuối nhân 3, kế tiếp nhân 1, xen kẽ
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $multiplier = (($totalLen - 1 - $i) % 2 === 0) ? 3 : 1;
            $sum       += (int) $digits[$i] * $multiplier;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Hoàn chỉnh GTIN bằng cách thêm check digit vào cuối.
     * Dùng khi người dùng nhập GTIN chưa có check digit.
     *
     * @param string $partialGtin GTIN chưa có check digit (7, 11, 12, hoặc 13 số)
     * @param int    $targetLen   Độ dài mong muốn (8, 12, 13, 14)
     */
    public function completeGTIN(string $partialGtin, int $targetLen = 13): string
    {
        $digits = preg_replace('/\D/', '', $partialGtin);
        $digits = str_pad($digits, $targetLen - 1, '0', STR_PAD_LEFT);
        return $digits . $this->gs1CheckDigit($digits, $targetLen);
    }

    // ─────────────────────────────────────────────────────────────
    // GLN
    // ─────────────────────────────────────────────────────────────

    /**
     * Tạo GLN nội bộ cho doanh nghiệp khi được duyệt.
     *
     * Format: 8930000 {enterpriseId 5 chữ số} {check digit}
     * Ví dụ: enterprise_id=1  → 8930000 00001 C → 8930000000018
     *        enterprise_id=42 → 8930000 00042 C → 8930000000423
     *
     * Ghi chú: Trong triển khai thực tế, GLN phải được đăng ký chính thức
     * với GS1 Việt Nam (http://gs1.org.vn). Mã này chỉ dùng cho mục đích
     * nghiên cứu / luận văn.
     *
     * @param int $enterpriseId ID của enterprise trong hệ thống
     */
    public function generateGLN(int $enterpriseId): string
    {
        $locationRef = str_pad($enterpriseId, 5, '0', STR_PAD_LEFT); // 5 chữ số
        $base        = self::SYSTEM_GLN_PREFIX . $locationRef;        // 12 chữ số
        $checkDigit  = $this->gs1CheckDigit($base, 13);
        return $base . $checkDigit; // 13 chữ số
    }

    /**
     * Kiểm tra GLN hợp lệ (13 số + check digit đúng).
     */
    public function validateGLN(string $gln): bool
    {
        $gln = preg_replace('/\D/', '', $gln);
        if (strlen($gln) !== 13) return false;
        return $this->gs1CheckDigit(substr($gln, 0, 12), 13) === (int) substr($gln, -1);
    }

    /**
     * Format GLN thành dạng hiển thị: 893 0000 00001 8
     */
    public function formatGLN(string $gln): string
    {
        $gln = preg_replace('/\D/', '', $gln);
        if (strlen($gln) !== 13) return $gln;
        return substr($gln, 0, 3) . ' '
            . substr($gln, 3, 4) . ' '
            . substr($gln, 7, 5) . ' '
            . substr($gln, 12, 1);
    }

    // ─────────────────────────────────────────────────────────────
    // SGTIN & GS1-128 Lot
    // ─────────────────────────────────────────────────────────────

    /**
     * Tạo SGTIN (Serialised GTIN) dùng trong EPCIS / IPFS payload.
     *
     * Format EPC URI: urn:epc:id:sgtin:{companyPrefix}.{itemRef}.{serial}
     * Ở đây dùng lotNumber làm serial vì hệ thống quản lý theo lô.
     *
     * Ví dụ: urn:epc:id:sgtin:8930000.0000001.LG07001
     *
     * @param string $gtin      GTIN 13 số của sản phẩm
     * @param string $lotNumber Mã lô hàng (batch code)
     */
    public function buildSGTIN(string $gtin, string $lotNumber): string
    {
        $gtin = preg_replace('/\D/', '', $gtin);

        // Tách company prefix (7 số) và item reference (5 số + check digit)
        $companyPrefix = substr($gtin, 0, 7);
        $itemRef       = substr($gtin, 7, 5); // không kèm check digit

        return "urn:epc:id:sgtin:{$companyPrefix}.{$itemRef}.{$lotNumber}";
    }

    /**
     * Tạo GS1-128 Application Identifier string cho lô hàng.
     * Dùng trong IPFS payload field `gs1_128`.
     *
     * AI (01) = GTIN
     * AI (10) = Batch/Lot Number
     * AI (17) = Best Before Date (YYMMDD)
     * AI (3102) = Net Weight (kg, 2 decimals) — nếu có
     *
     * Ví dụ: (01)08930000000018(10)LG07001(17)270307
     *
     * @param string      $gtin       GTIN 13 → pad lên 14 (GTIN-14)
     * @param string      $lotNumber  Mã lô
     * @param string|null $expiryDate Ngày hết hạn (Y-m-d hoặc d/m/Y)
     */
    public function buildGS1_128(string $gtin, string $lotNumber, ?string $expiryDate = null): string
    {
        // Dùng buildProductTraceCode để đảm bảo GTIN-14 có check digit đúng
        // (không được pad đơn giản — phải bỏ check digit cũ rồi tính lại)
        $ai01Str = $this->buildProductTraceCode($gtin);
        $gtin14  = $this->extractGtin14FromAi01($ai01Str) ?? str_pad(preg_replace('/\D/', '', $gtin), 14, '0', STR_PAD_LEFT);
        $ai      = "(01){$gtin14}(10){$lotNumber}";

        if ($expiryDate) {
            try {
                $date   = \Carbon\Carbon::parse($expiryDate);
                $ai    .= '(17)' . $date->format('ymd');
            } catch (\Throwable) {}
        }

        return $ai;
    }

    /**
     * Tạo full GS1 identifier block để nhúng vào IPFS payload khi publish event.
     *
     * @param string      $gtin         GTIN của sản phẩm
     * @param string      $lotNumber    Mã lô
     * @param string|null $gln          GLN của doanh nghiệp
     * @param string|null $expiryDate   Hạn sử dụng
     */
    public function buildIdentifiers(
        string  $gtin,
        string  $lotNumber,
        ?string $gln        = null,
        ?string $expiryDate = null,
    ): array {
        $cleanGtin = preg_replace('/\D/', '', $gtin);

        return [
            'gtin'         => $cleanGtin,
            'gtin_valid'   => $this->validateGTIN($cleanGtin),
            'gln'          => $gln,
            'lot'          => $lotNumber,
            'sgtin'        => $cleanGtin ? $this->buildSGTIN($cleanGtin, $lotNumber) : null,
            'gs1_128'      => $cleanGtin ? $this->buildGS1_128($cleanGtin, $lotNumber, $expiryDate) : null,
            // Mã truy vết vật phẩm AI(01) — TCVN 13274:2020 Bảng 1
            'ai01_product' => $cleanGtin ? $this->buildProductTraceCode($cleanGtin) : null,
            'standard_ref' => 'GS1 General Specifications v24 / TCVN 12850:2019',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // MÃ TRUY VẾT VẬT PHẨM — AI (01) theo TCVN 13274:2020 Bảng 1
    // ─────────────────────────────────────────────────────────────────

    /**
     * Tạo mã truy vết vật phẩm theo TCVN 13274:2020 — Bảng 1.
     *
     * Cấu trúc GTIN-14:
     *   N1        = Số chỉ thị (packaging level: 0=item, 1-8=bao gói, 9=biến đổi)
     *   N2–N7/N10 = Tiền tố mã doanh nghiệp (GS1 Company Prefix)
     *   N8–N13    = Số tham chiếu vật phẩm (Item Reference)
     *   N14       = Số kiểm tra (Check digit)
     *
     * Format chuẩn in lên tem/nhãn: (01)NNNNNNNNNNNNNN
     *
     * @param string $gtin         GTIN gốc (8, 12, 13 hoặc 14 chữ số)
     * @param int    $packagingLevel  Cấp độ bao gói N1 (0–9), mặc định 0 = đơn vị hàng hoá
     * @return string              Chuỗi AI(01) đầy đủ: "(01)NNNNNNNNNNNNNN"
     */
    public function buildProductTraceCode(string $gtin, int $packagingLevel = 0): string
    {
        $digits = preg_replace('/\D/', '', $gtin);

        // Pad lên 13 chữ số (bỏ check digit cuối nếu đã có)
        // rồi thêm N1 vào đầu → tổng 14 chữ số
        if (strlen($digits) === 14) {
            // Đã là GTIN-14: thay N1 bằng packagingLevel
            $base14 = str_pad($packagingLevel, 1) . substr($digits, 1, 12);
        } elseif (strlen($digits) === 13) {
            // GTIN-13: thêm N1 vào đầu, bỏ check digit cũ
            $base14 = $packagingLevel . substr($digits, 0, 12);
        } else {
            // GTIN-8 / GTIN-12: pad trái về 12 số, thêm N1
            $padded = str_pad($digits, 12, '0', STR_PAD_LEFT);
            $base14 = $packagingLevel . substr($padded, 0, 12);
        }

        // Tính lại check digit cho chuỗi 14 chữ số
        $checkDigit = $this->gs1CheckDigit(substr($base14, 0, 13), 14);
        $gtin14     = $base14 . $checkDigit;

        return "(01){$gtin14}";
    }

    /**
     * Trích GTIN-14 thuần (không có AI prefix) từ chuỗi AI(01).
     *
     * @param string $ai01String  Ví dụ: "(01)08930000000018"
     * @return string|null        Ví dụ: "08930000000018"
     */
    public function extractGtin14FromAi01(string $ai01String): ?string
    {
        if (preg_match('/\(01\)(\d{14})/', $ai01String, $m)) {
            return $m[1];
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────────
    // MÃ TRUY VẾT ĐỊA ĐIỂM — AI (410–417) theo TCVN 13274:2020 Bảng 4
    // ─────────────────────────────────────────────────────────────────

    /**
     * Các AI type địa điểm được định nghĩa trong TCVN 13274:2020 Bảng 4.
     */
    public const LOCATION_AI_TYPES = [
        '410' => 'ship_to',          // Địa điểm nhận hàng
        '411' => 'bill_to',          // Địa điểm gửi hàng / bill-to
        '412' => 'purchased_from',   // Địa điểm mua hàng / nhà cung cấp
        '414' => 'physical',         // Địa điểm vật lý chung
        '416' => 'production',       // Địa điểm sản xuất / vùng trồng
        '417' => 'party',            // Địa điểm giao dịch
    ];

    /**
     * Tạo mã truy vết địa điểm theo TCVN 13274:2020 — Bảng 4.
     *
     * Cấu trúc: AI(4xx) + GLN (13 chữ số)
     * Format in lên tem/nhãn: "(416)8930000000018"
     *
     * @param string $aiType  Loại AI: '410', '411', '412', '414', '416', '417'
     * @param string $gln     GLN 13 chữ số của địa điểm
     * @return string         Chuỗi AI đầy đủ: "(416)NNNNNNNNNNNNN"
     * @throws \InvalidArgumentException nếu aiType không hợp lệ hoặc GLN sai
     */
    public function buildLocationCode(string $aiType, string $gln): string
    {
        if (! array_key_exists($aiType, self::LOCATION_AI_TYPES)) {
            throw new \InvalidArgumentException(
                "AI type '{$aiType}' không hợp lệ. Chỉ chấp nhận: "
                . implode(', ', array_keys(self::LOCATION_AI_TYPES))
            );
        }

        $cleanGln = preg_replace('/\D/', '', $gln);

        if (! $this->validateGLN($cleanGln)) {
            throw new \InvalidArgumentException(
                "GLN '{$gln}' không hợp lệ. Phải là 13 chữ số với check digit đúng."
            );
        }

        return "({$aiType}){$cleanGln}";
    }

    /**
     * Tạo mã địa điểm mà không validate (dùng khi GLN là nội bộ / demo).
     *
     * @param string $aiType  Loại AI: '410'–'417'
     * @param string $gln     GLN bất kỳ (tự động pad về 13 số nếu thiếu)
     */
    public function buildLocationCodeLoose(string $aiType, string $gln): string
    {
        $cleanGln = str_pad(preg_replace('/\D/', '', $gln), 13, '0', STR_PAD_LEFT);
        return "({$aiType}){$cleanGln}";
    }

    /**
     * Tạo GS1-128 đầy đủ cho một lô hàng kèm theo địa điểm sản xuất (AI 416).
     *
     * Ví dụ: "(01)08930000000018(10)LG07001(17)270307(416)8930000000018"
     *
     * @param string      $gtin       GTIN sản phẩm
     * @param string      $lotNumber  Mã lô
     * @param string|null $expiryDate Hạn sử dụng
     * @param string|null $locationGln GLN của địa điểm sản xuất
     * @param string      $locationAi  AI type địa điểm (mặc định '416')
     */
    public function buildGS1_128WithLocation(
        string  $gtin,
        string  $lotNumber,
        ?string $expiryDate  = null,
        ?string $locationGln = null,
        string  $locationAi  = '416',
    ): string {
        $ai = $this->buildGS1_128($gtin, $lotNumber, $expiryDate);

        if ($locationGln) {
            $cleanGln = preg_replace('/\D/', '', $locationGln);
            $ai .= "({$locationAi}){$cleanGln}";
        }

        return $ai;
    }

    // ─────────────────────────────────────────────────────────────────
    // MỚI: buildFullLabel — tất cả AI codes cho 1 lô (TT02 / TCVN 13274)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Tạo toàn bộ AI codes cho một lô hàng theo TT02 + TCVN 13274:2020.
     *
     * Bắt buộc: AI(01) GTIN-14, AI(10) Lot, AI(11) sản xuất, AI(17) hạn dùng
     * Tùy chọn: AI(251) event_code, AI(414/416) location GLN, AI(417) enterprise GLN
     *
     * @param \App\Models\Batch        $batch  Lô hàng cần tạo label
     * @param \App\Models\TraceEvent|null $event  Event origin (lấy event_code cho AI 251)
     */
    public function buildFullLabel(\App\Models\Batch $batch, ?\App\Models\TraceEvent $event = null): array
    {
        // Resolve GTIN-14
        $rawGtin   = $batch->gtin_cached ?? $batch->product?->gtin ?? '';
        $ai01Str   = $rawGtin ? $this->buildProductTraceCode($rawGtin) : null;
        $gtin14    = $ai01Str ? ($this->extractGtin14FromAi01($ai01Str) ?? '') : '';

        // AI(10) — mã lô
        $ai10 = $batch->code ? "(10){$batch->code}" : null;

        // AI(11) — ngày sản xuất YYMMDD
        $ai11 = $batch->production_date
            ? '(11)' . \Carbon\Carbon::parse($batch->production_date)->format('ymd')
            : null;

        // AI(17) — hạn sử dụng YYMMDD
        $ai17 = $batch->expiry_date
            ? '(17)' . \Carbon\Carbon::parse($batch->expiry_date)->format('ymd')
            : null;

        // AI(251) — mã tham chiếu nguồn gốc (event_code)
        $originEvent = $event ?? $batch->originEvent;
        $ai251 = $originEvent?->event_code
            ? "(251){$originEvent->event_code}"
            : null;

        // AI(416) — GLN địa điểm sản xuất (từ trace_location của event)
        $locationGln = $originEvent?->traceLocation?->gln;
        $ai416 = $locationGln ? "(416){$locationGln}" : null;

        // AI(414) — GLN địa điểm vật lý (fallback nếu không có 416)
        $ai414 = (!$ai416 && $locationGln) ? "(414){$locationGln}" : null;

        // AI(417) — GLN doanh nghiệp
        $enterpriseGln = $batch->enterprise?->gln
            ?? $this->generateGLN($batch->enterprise_id);
        $ai417 = "(417){$enterpriseGln}";

        // Full GS1-128 string (ghép các AI bắt buộc + tùy chọn)
        $gs1Parts = array_filter([
            $ai01Str, $ai10, $ai11, $ai17, $ai251, $ai416 ?? $ai414, $ai417
        ]);
        $gs1_128 = implode('', $gs1Parts);

        // GS1 Digital Link URL
        $domain      = config('app.url', 'https://traceability.local');
        $digitalLink = $gtin14
            ? rtrim($domain, '/') . "/01/{$gtin14}" . ($batch->code ? "/10/{$batch->code}" : '')
            : null;

        return [
            'ai01'         => $ai01Str,
            'ai10'         => $ai10,
            'ai11'         => $ai11,
            'ai17'         => $ai17,
            'ai251'        => $ai251,
            'ai414'        => $ai414,
            'ai416'        => $ai416,
            'ai417'        => $ai417,
            'gs1_128'      => $gs1_128,
            'digital_link' => $digitalLink,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // MỚI: buildSscc — Serial Shipping Container Code AI(00)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Tạo SSCC 18 số chuẩn GS1.
     *
     * Cấu trúc: E + Company Prefix (7) + Serial (9) + Check digit
     *   Extension digit E = 0-9 (mặc định 0)
     *   Company Prefix  = SYSTEM_GLN_PREFIX (8930000)
     *   Serial          = enterpriseId padded + seq padded
     *
     * Format AI: "(00)E{companyPrefix}{serial}{check}"
     */
    public function buildSscc(int $enterpriseId, int $seq, int $extension = 0): string
    {
        // Extension (1) + Company Prefix (7) + enterprise part (4) + seq part (5) = 17 digits → check = 18
        $entPart    = str_pad($enterpriseId, 4, '0', STR_PAD_LEFT);
        $seqPart    = str_pad($seq, 5, '0', STR_PAD_LEFT);
        $base17     = $extension . self::SYSTEM_GLN_PREFIX . $entPart . $seqPart; // 17 digits
        $checkDigit = $this->gs1CheckDigit($base17, 18);

        return "(00){$base17}{$checkDigit}";
    }

    // ─────────────────────────────────────────────────────────────────
    // MỚI: validateEventCode — validate format event_code
    // ─────────────────────────────────────────────────────────────────

    /**
     * Validate format event_code chuẩn hệ thống.
     *
     * Format: EVT-{ENT_CODE 2-10 chars}-{CTE_CODE 2-10 chars}-{YYYYMM 6 digits}-{SEQ 3 digits}
     * Ví dụ: EVT-AGU-HARVEST-202603-001
     */
    public function validateEventCode(string $code): bool
    {
        return (bool) preg_match(
            '/^EVT-[A-Z0-9]{2,10}-[A-Z0-9]{2,10}-\d{6}-\d{3}$/',
            strtoupper(trim($code))
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // MỚI: parseGs1String — parse chuỗi GS1-128 → array [ai => value]
    // ─────────────────────────────────────────────────────────────────

    /**
     * Parse chuỗi GS1-128 với các AI trong dấu ngoặc.
     *
     * Ví dụ input:  "(01)08938503000018(10)LOT-001(17)270315"
     * Ví dụ output: ['01' => '08938503000018', '10' => 'LOT-001', '17' => '270315']
     *
     * Hỗ trợ:
     *  - AI có độ dài cố định (01, 11, 17, 00, ...): đọc đúng số ký tự
     *  - AI có độ dài biến đổi (10, 251, 400, ...): đọc đến AI tiếp theo
     *
     * @param string $gs1 Chuỗi GS1-128 đầy đủ
     * @return array      [ai_code => value]
     */
    public function parseGs1String(string $gs1): array
    {
        // Độ dài cố định của các AI thông dụng (không tính AI prefix)
        // Key = AI code, Value = số ký tự của value (null = variable length)
        $fixedLengths = [
            '00' => 18,  // SSCC
            '01' => 14,  // GTIN-14
            '02' => 14,  // GTIN-14 of contained
            '11' => 6,   // Production date YYMMDD
            '12' => 6,   // Due date
            '13' => 6,   // Packaging date
            '15' => 6,   // Best before date
            '17' => 6,   // Expiry date YYMMDD
            '20' => 2,   // Variant
            '31' => 6,   // Net weight (prefix 31xx)
            '32' => 6,
            '33' => 6,
            '34' => 6,
            '35' => 6,
            '36' => 6,
        ];

        $result  = [];
        $pattern = '/\((\d{2,4})\)/';  // match (NN) hoặc (NNNN)
        $matches = [];

        preg_match_all($pattern, $gs1, $matches, PREG_OFFSET_CAPTURE);

        $tokens = $matches[0]; // array of [fullMatch, offset]
        $ais    = $matches[1]; // AI codes

        for ($i = 0; $i < count($tokens); $i++) {
            $ai     = $ais[$i][0];
            $start  = $tokens[$i][1] + strlen($tokens[$i][0]); // char index after closing )
            $end    = isset($tokens[$i + 1]) ? $tokens[$i + 1][1] : strlen($gs1);

            // Nếu AI có độ dài cố định → cắt đúng số ký tự
            $prefix2 = substr($ai, 0, 2);
            if (isset($fixedLengths[$ai])) {
                $len   = $fixedLengths[$ai];
                $value = substr($gs1, $start, $len);
            } elseif (isset($fixedLengths[$prefix2])) {
                $len   = $fixedLengths[$prefix2];
                $value = substr($gs1, $start, $len);
            } else {
                // Variable length: đọc đến AI tiếp theo
                $value = substr($gs1, $start, $end - $start);
            }

            $result[$ai] = $value;
        }

        return $result;
    }
}