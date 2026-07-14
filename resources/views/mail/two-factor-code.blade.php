<!doctype html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;line-height:1.6">
    <div style="max-width:560px;margin:32px auto;padding:28px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px">
        <p style="margin:0;color:#7e22ce;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase">Mission Care Training Center</p>
        <h1 style="margin:12px 0 8px;font-size:24px">Verification code</h1>
        <p style="margin:0 0 20px">Hello {{ $recipientName }}, use this one-time code to finish signing in to the MCARE staff portal.</p>
        <div style="padding:16px;text-align:center;background:#f3e8ff;border:1px solid #e9d5ff;border-radius:12px">
            <span style="font-size:32px;font-weight:800;letter-spacing:.28em">{{ $code }}</span>
        </div>
        <p style="margin:20px 0 0;font-size:14px;color:#475569">This code expires in {{ $expiresInMinutes }} minutes. If you did not start this sign-in, change your password and notify an administrator.</p>
        <p style="margin:20px 0 0;font-size:12px;color:#64748b">MCARE will never ask you to share this code by chat or phone.</p>
    </div>
</body>
</html>
