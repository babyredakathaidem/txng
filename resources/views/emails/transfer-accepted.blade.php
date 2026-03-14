<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
    .card { background: #fff; border-radius: 8px; max-width: 560px; margin: 0 auto; padding: 32px; }
    .badge { display: inline-block; background: #16a34a; color: #fff; border-radius: 4px; padding: 4px 12px; font-size: 12px; font-weight: bold; }
    h2 { color: #1a1a1a; margin-top: 16px; }
    .info-row { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .info-label { color: #888; width: 140px; flex-shrink: 0; }
    .info-value { color: #333; font-weight: 500; }
    .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-top: 24px; }
    .footer { color: #aaa; font-size: 12px; margin-top: 24px; text-align: center; }
  </style>
</head>
<body>
<div class="card">
  <span class="badge">LÔ HÀNG ĐÃ ĐƯỢC NHẬN</span>
  <h2>{{ $transfer->toEnterprise->name }} đã xác nhận nhận lô hàng</h2>

  <p style="color:#555; font-size:14px;">
    Yêu cầu chuyển giao của bạn đã được xác nhận thành công. Lô hàng đã được bàn giao cho đối tác.
  </p>

  <div style="margin: 20px 0;">
    <div class="info-row">
      <span class="info-label">Lô hàng</span>
      <span class="info-value">{{ $transfer->batch->code }} — {{ $transfer->batch->product_name }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Đến doanh nghiệp</span>
      <span class="info-value">{{ $transfer->toEnterprise->name }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Số lượng</span>
      <span class="info-value">{{ $transfer->quantity }} {{ $transfer->unit }}</span>
    </div>
    @if($transfer->invoice_no)
    <div class="info-row">
      <span class="info-label">Số hóa đơn</span>
      <span class="info-value">{{ $transfer->invoice_no }}</span>
    </div>
    @endif
    <div class="info-row">
      <span class="info-label">Thời gian nhận</span>
      <span class="info-value">{{ $transfer->accepted_at?->format('H:i d/m/Y') }}</span>
    </div>
  </div>

  <a href="{{ url('/batches') }}" class="btn">Xem danh sách lô hàng</a>

  <p class="footer">AGU Traceability by Stewie</p>
</div>
</body>
</html>