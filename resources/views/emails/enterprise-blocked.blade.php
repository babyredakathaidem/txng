<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <div style="background:#6b7280;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:24px">
    🔒 <strong>THÔNG BÁO KHÓA TÀI KHOẢN</strong>
  </div>

  <p>Xin chào <strong>{{ $enterprise->name }}</strong>,</p>
  <p>Tài khoản doanh nghiệp của bạn trên hệ thống <strong>AGU Traceability</strong> đã bị tạm khóa.</p>

  <div style="background:#f9f9f9;border-left:4px solid #6b7280;padding:16px;border-radius:0 8px 8px 0;margin:20px 0">
    <p style="margin:0 0 6px;color:#666;font-size:13px">Lý do:</p>
    <p style="margin:0;font-weight:bold;color:#111">{{ $enterprise->blocked_reason }}</p>
  </div>

  <p style="color:#666;font-size:14px">
    Nếu bạn cho rằng đây là nhầm lẫn hoặc muốn khiếu nại, vui lòng liên hệ quản trị viên hệ thống.
  </p>

  <p style="color:#999;font-size:12px;margin-top:24px">
    Thời gian khóa: {{ $enterprise->blocked_at?->format('H:i d/m/Y') }}
  </p>
</div>
</body>
</html>