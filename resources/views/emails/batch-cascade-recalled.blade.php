<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8" /></head>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px;margin:0">
<div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.1)">

  <!-- Header cảnh báo -->
  <div style="background:#b91c1c;color:#fff;padding:16px 20px;border-radius:8px;margin-bottom:24px">
    <div style="font-size:18px;font-weight:bold">⚠️ CẢNH BÁO: THU HỒI DÂY CHUYỀN</div>
    <div style="font-size:13px;margin-top:4px;opacity:.9">
      Hệ thống AGU Traceability — TCVN 12850:2019
    </div>
  </div>

  <p style="color:#374151;line-height:1.6">
    Doanh nghiệp của bạn đang sở hữu <strong>{{ $affectedBatches->count() }} lô hàng</strong>
    có nguồn gốc từ lô <strong>{{ $rootBatch->code }}</strong> vừa bị phát lệnh thu hồi.
    Toàn bộ các lô liên quan dưới đây đã bị <strong style="color:#b91c1c">đình chỉ lưu thông</strong>
    theo quy định Thu hồi dây chuyền.
  </p>

  <!-- Thông tin lô gốc -->
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin:20px 0">
    <div style="font-weight:bold;color:#991b1b;margin-bottom:10px">📋 Lệnh thu hồi gốc</div>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <tr>
        <td style="padding:5px 8px;color:#6b7280;width:45%">Mã lô bị thu hồi</td>
        <td style="padding:5px 8px;font-weight:bold;color:#b91c1c">{{ $rootBatch->code }}</td>
      </tr>
      <tr style="background:#fff5f5">
        <td style="padding:5px 8px;color:#6b7280">Sản phẩm</td>
        <td style="padding:5px 8px">{{ $rootBatch->product?->name ?? $rootBatch->product_name }}</td>
      </tr>
      <tr>
        <td style="padding:5px 8px;color:#6b7280">DN phát lệnh</td>
        <td style="padding:5px 8px">{{ $rootBatch->enterprise?->name ?? 'N/A' }}</td>
      </tr>
      <tr style="background:#fff5f5">
        <td style="padding:5px 8px;color:#6b7280">Thời gian thu hồi</td>
        <td style="padding:5px 8px">{{ $rootRecall->recalled_at?->format('H:i — d/m/Y') }}</td>
      </tr>
      <tr>
        <td style="padding:5px 8px;color:#6b7280;vertical-align:top">Lý do thu hồi</td>
        <td style="padding:5px 8px;color:#b91c1c;font-weight:bold">{{ $rootRecall->reason }}</td>
      </tr>
      @if($rootRecall->notice_content)
      <tr style="background:#fff5f5">
        <td style="padding:5px 8px;color:#6b7280;vertical-align:top">Hướng dẫn xử lý</td>
        <td style="padding:5px 8px">{{ $rootRecall->notice_content }}</td>
      </tr>
      @endif
      @if($rootRecall->ipfs_cid)
      <tr>
        <td style="padding:5px 8px;color:#6b7280">Bằng chứng IPFS</td>
        <td style="padding:5px 8px;font-family:monospace;font-size:12px;color:#4f46e5">
          {{ $rootRecall->ipfs_cid }}
        </td>
      </tr>
      @endif
    </table>
  </div>

  <!-- Danh sách lô bị ảnh hưởng -->
  <div style="margin:20px 0">
    <div style="font-weight:bold;color:#374151;margin-bottom:10px">
      📦 Lô hàng của bạn bị ảnh hưởng ({{ $affectedBatches->count() }} lô)
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
      <thead>
        <tr style="background:#f9fafb">
          <th style="padding:8px 12px;text-align:left;color:#6b7280;font-weight:600">Mã lô</th>
          <th style="padding:8px 12px;text-align:left;color:#6b7280;font-weight:600">Sản phẩm</th>
          <th style="padding:8px 12px;text-align:left;color:#6b7280;font-weight:600">Loại</th>
        </tr>
      </thead>
      <tbody>
        @foreach($affectedBatches as $i => $affected)
        <tr style="{{ $i % 2 === 0 ? 'background:#fff' : 'background:#f9fafb' }}">
          <td style="padding:8px 12px;font-weight:bold;font-family:monospace;color:#b91c1c">
            {{ $affected->code }}
          </td>
          <td style="padding:8px 12px;color:#374151">
            {{ $affected->getDisplayName() }}
          </td>
          <td style="padding:8px 12px;color:#6b7280">
            @switch($affected->batch_type)
              @case('split') Lô tách @break
              @case('merged') Lô gộp @break
              @case('received') Lô nhận @break
              @default Lô gốc
            @endswitch
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Hành động cần thực hiện -->
  <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:16px;margin:20px 0">
    <div style="font-weight:bold;color:#92400e;margin-bottom:8px">🔔 Hành động cần thực hiện ngay</div>
    <ul style="color:#78350f;font-size:14px;line-height:1.8;margin:0;padding-left:20px">
      <li>Dừng ngay việc lưu thông và phân phối các lô hàng nêu trên.</li>
      <li>Cách ly và kiểm tra toàn bộ hàng tồn kho liên quan.</li>
      <li>Đăng nhập hệ thống AGU Traceability để xem chi tiết và cập nhật trạng thái.</li>
      <li>Liên hệ cơ quan chức năng nếu cần thiết theo quy định hiện hành.</li>
    </ul>
  </div>

  <!-- CTA Button -->
  <div style="text-align:center;margin:24px 0">
    <a href="{{ url('/batches') }}"
      style="display:inline-block;background:#b91c1c;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:15px">
      Xem và xử lý ngay trong hệ thống
    </a>
  </div>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0" />

  <p style="color:#9ca3af;font-size:12px;text-align:center;line-height:1.6">
    Email này được gửi tự động bởi hệ thống AGU Traceability.<br />
    Tuân thủ TCVN 12850:2019 — Truy xuất nguồn gốc thực phẩm.
  </p>
</div>
</body>
</html>