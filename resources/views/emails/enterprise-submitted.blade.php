<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <h2 style="color:#e97316"> Doanh nghiệp mới đăng ký</h2>
  <p>Có một doanh nghiệp vừa nộp hồ sơ đăng ký hệ thống AGU Traceability.</p>

  <table style="width:100%;border-collapse:collapse;margin:20px 0">
    <tr><td style="padding:8px;color:#666">Tên DN</td><td style="padding:8px;font-weight:bold">{{ $enterprise->name }}</td></tr>
    <tr style="background:#f9f9f9"><td style="padding:8px;color:#666">Mã số DN</td><td style="padding:8px">{{ $enterprise->business_code }}</td></tr>
    <tr><td style="padding:8px;color:#666">Email</td><td style="padding:8px">{{ $enterprise->email }}</td></tr>
    <tr style="background:#f9f9f9"><td style="padding:8px;color:#666">SĐT</td><td style="padding:8px">{{ $enterprise->phone }}</td></tr>
    <tr><td style="padding:8px;color:#666">Người đại diện</td><td style="padding:8px">{{ $enterprise->representative_name }}</td></tr>
    <tr style="background:#f9f9f9"><td style="padding:8px;color:#666">Người nộp</td><td style="padding:8px">{{ $submitter->name }} ({{ $submitter->email }})</td></tr>
  </table>

  <a href="{{ url('/sys/enterprises') }}"
     style="display:inline-block;background:#e97316;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
    Xét duyệt ngay
  </a>
</div>
</body>
</html>