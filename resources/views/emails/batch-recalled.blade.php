<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <div style="background:#ef4444;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:24px">
    ⚠️ <strong>CẢNH BÁO THU HỒI LÔ HÀNG</strong>
  </div>

  <p>Một lô hàng trong hệ thống AGU Traceability vừa được phát lệnh thu hồi.</p>

  <table style="width:100%;border-collapse:collapse;margin:20px 0">
    <tr><td style="padding:8px;color:#666;width:40%">Mã lô</td>
        <td style="padding:8px;font-weight:bold">{{ $batch->code }}</td></tr>
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Sản phẩm</td>
        <td style="padding:8px">{{ $batch->product?->name ?? $batch->product_name }}</td></tr>
    <tr><td style="padding:8px;color:#666">Thời gian</td>
        <td style="padding:8px">{{ $recall->recalled_at?->format('H:i d/m/Y') }}</td></tr>
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Người thực hiện</td>
        <td style="padding:8px">{{ $recall->recalledBy?->name }}</td></tr>
    <tr><td style="padding:8px;color:#666">Lý do</td>
        <td style="padding:8px;color:#ef4444">{{ $recall->reason }}</td></tr>
    @if($recall->notice_content)
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Nội dung thông báo</td>
        <td style="padding:8px">{{ $recall->notice_content }}</td></tr>
    @endif
  </table>

  <p style="color:#666;font-size:14px">
    Vui lòng kiểm tra và xử lý kịp thời. Đăng nhập hệ thống để xem chi tiết.
  </p>

  <a href="{{ url('/batches') }}"
     style="display:inline-block;background:#ef4444;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
    Xem chi tiết lô hàng
  </a>
</div>
</body>
</html>