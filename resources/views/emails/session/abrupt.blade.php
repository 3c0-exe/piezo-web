<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Stopped</title>
<style>
  body { margin: 0; padding: 0; background: #0f172a; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 560px; margin: 40px auto; background: #1e293b; border-radius: 16px; overflow: hidden; border: 1px solid #334155; }
  .header { background: linear-gradient(135deg, #7c1d1d 0%, #b91c1c 100%); padding: 36px 40px; text-align: center; }
  .header-icon { font-size: 48px; display: block; margin-bottom: 8px; }
  .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: #fecaca; font-size: 14px; }
  .body { padding: 36px 40px; }
  .greeting { font-size: 16px; color: #e2e8f0; margin: 0 0 20px; }
  .message { font-size: 15px; color: #94a3b8; line-height: 1.7; margin: 0 0 28px; }
  .card { background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; }
  .card-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; }
  .card-row:last-child { border-bottom: none; }
  .card-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .card-value { font-size: 14px; color: #e2e8f0; font-weight: 600; }
  .tip { background: #1c0a0a; border: 1px solid #7f1d1d; border-radius: 10px; padding: 16px 20px; font-size: 13px; color: #fca5a5; line-height: 1.6; }
  .footer { padding: 20px 40px; border-top: 1px solid #334155; text-align: center; }
  .footer p { margin: 0; font-size: 12px; color: #475569; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <span class="header-icon">⚠️</span>
    <h1>Session Stopped by Admin</h1>
    <p>Your charging session was ended early by an administrator.</p>
  </div>

  <div class="body">
    <p class="greeting">Hi, <strong style="color:#f87171">{{ $session->student_name }}</strong>.</p>

    <p class="message">
      Your charging session has been stopped early by an admin. This may happen due to scheduling or system management reasons. Thank you for your understanding!
    </p>

    <div class="card">
      <div class="card-row">
        <span class="card-label">Session Started</span>
        <span class="card-value">{{ $session->started_at->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Stopped At</span>
        <span class="card-value">{{ now()->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Date</span>
        <span class="card-value">{{ $session->started_at->format('F j, Y') }}</span>
      </div>
      @if($session->total_steps)
      <div class="card-row">
        <span class="card-label">Steps Recorded</span>
        <span class="card-value">{{ number_format($session->total_steps) }}</span>
      </div>
      @endif
    </div>

    <div class="tip">
      <strong>📌 Action Required:</strong> Please disconnect your device from the charging pad. If you have questions about why your session was stopped, please approach the administrator on duty.
    </div>
  </div>

  <div class="footer">
    <p>This is an automated message from <strong style="color:#94a3b8">Piezo Dashboard</strong> · SPCC<br>Please do not reply to this email.</p>
  </div>

</div>
</body>
</html>