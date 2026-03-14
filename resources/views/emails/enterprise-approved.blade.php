<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <h2 style="color:#16a34a">Doanh nghiệp đã được duyệt</h2>
  <p>Xin chúc mừng! Doanh nghiệp <strong>{{ $enterprise->name }}</strong> đã được duyệt tham gia hệ thống AGU Traceability.</p>
  <p>Bạn có thể đăng nhập để sử dụng đầy đủ tính năng.</p>

  <a href="{{ url('/login') }}"
     style="display:inline-block;background:#e97316;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
    Đăng nhập ngay
  </a>
</div>
</body>
</html>