<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <h2 style="color:#f97316">Tài khoản nhân viên AGU Traceability</h2>
  <p>Xin chào <strong>{{ $staff->name }}</strong>,</p>
  <p>Tài khoản nhân viên của bạn tại doanh nghiệp <strong>{{ $enterprise->name }}</strong> đã được tạo.</p>

  <div style="background:#f9f9f9;border-radius:8px;padding:20px;margin:20px 0">
    <p style="margin:0 0 8px;color:#666">Thông tin đăng nhập:</p>
    <p style="margin:4px 0"><strong>Email:</strong> {{ $staff->email }}</p>
    <p style="margin:4px 0"><strong>Mật khẩu tạm:</strong>
      <span style="font-family:monospace;background:#e5e7eb;padding:2px 8px;border-radius:4px">
        {{ $plainPassword }}
      </span>
    </p>
  </div>

  <p style="color:#ef4444;font-size:13px">⚠️ Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu.</p>

  <a href="{{ url('/login') }}"
     style="display:inline-block;background:#f97316;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
    Đăng nhập ngay
  </a>

  <p style="color:#999;font-size:12px;margin-top:24px">
    Nếu bạn không yêu cầu tạo tài khoản này, vui lòng liên hệ quản trị viên.
  </p>
</div>
</body>
</html>