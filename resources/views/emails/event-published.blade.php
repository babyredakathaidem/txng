<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f4f4f4;padding:30px">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:32px">
  <h2 style="color:#22c55e">✅ Sự kiện đã được ghi nhận lên IPFS</h2>

  <table style="width:100%;border-collapse:collapse;margin:20px 0">
    <tr><td style="padding:8px;color:#666;width:40%">Mã lô</td>
        <td style="padding:8px;font-weight:bold">{{ $event->batch?->code }}</td></tr>
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Sản phẩm</td>
        <td style="padding:8px">{{ $event->batch?->product?->name }}</td></tr>
    <tr><td style="padding:8px;color:#666">Loại sự kiện</td>
        <td style="padding:8px">{{ $event->cte_code }}</td></tr>
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Thời gian publish</td>
        <td style="padding:8px">{{ $event->published_at?->format('H:i d/m/Y') }}</td></tr>
    <tr><td style="padding:8px;color:#666">IPFS CID</td>
        <td style="padding:8px;font-family:monospace;font-size:12px;word-break:break-all">
          {{ $event->ipfs_cid }}</td></tr>
    <tr style="background:#f9f9f9">
        <td style="padding:8px;color:#666">Content Hash</td>
        <td style="padding:8px;font-family:monospace;font-size:12px;word-break:break-all">
          {{ $event->content_hash }}</td></tr>
  </table>

  <div style="display:flex;gap:12px;flex-wrap:wrap">
    @if($event->ipfs_url)
    <a href="{{ $event->ipfs_url }}"
       style="display:inline-block;background:#22c55e;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px">
      Xem trên IPFS
    </a>
    @endif
    <a href="{{ url('/events?batch_id='.$event->batch_id) }}"
       style="display:inline-block;background:#f97316;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px">
      Xem sự kiện
    </a>
  </div>

  <p style="color:#999;font-size:12px;margin-top:24px">
    Dữ liệu đã được ghi bất biến theo chuẩn TCVN 12850:2019.
  </p>
</div>
</body>
</html>