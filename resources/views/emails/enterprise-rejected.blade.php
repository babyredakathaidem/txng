<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <h2 style="color:#dc2626"> Hồ sơ chưa được chấp thuận</h2>
  <p>Rất tiếc, hồ sơ doanh nghiệp <strong>{{ $enterprise->name }}</strong> chưa được duyệt.</p>

  @if($enterprise->rejection_reason)
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin:16px 0">
    <strong style="color:#dc2626">Lý do:</strong>
    <p style="margin:8px 0 0;color:#7f1d1d">{{ $enterprise->rejection_reason }}</p>
  </div>
  @endif

  <p>Vui lòng liên hệ với hệ thống hoặc đăng nhập để xem chi tiết.</p>

  <a href="{{ url('/login') }}"
     style="display:inline-block;background:#e97316;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
    Đăng nhập
  </a>
</div>
</body>
</html>