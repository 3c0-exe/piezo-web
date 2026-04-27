<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Started</title>
<style>
  body { margin: 0; padding: 0; background: #0f172a; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 560px; margin: 40px auto; background: #1e293b; border-radius: 16px; overflow: hidden; border: 1px solid #334155; }
  .header { background: linear-gradient(135deg, #166534 0%, #15803d 100%); padding: 36px 40px; text-align: center; }
  .header-icon { font-size: 48px; display: block; margin-bottom: 8px; }
  .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
  .header p { margin: 6px 0 0; color: #bbf7d0; font-size: 14px; }
  .body { padding: 36px 40px; }
  .greeting { font-size: 16px; color: #e2e8f0; margin: 0 0 20px; }
  .message { font-size: 15px; color: #94a3b8; line-height: 1.7; margin: 0 0 28px; }
  .card { background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; }
  .card-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; }
  .card-row:last-child { border-bottom: none; }
  .card-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .card-value { font-size: 14px; color: #e2e8f0; font-weight: 600; }
  .badge { display: inline-block; background: #166534; color: #86efac; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 99px; border: 1px solid #15803d; }
  .tip { background: #0c2a16; border: 1px solid #166534; border-radius: 10px; padding: 16px 20px; font-size: 13px; color: #86efac; line-height: 1.6; }
  .tip strong { color: #4ade80; }
  .footer { padding: 20px 40px; border-top: 1px solid #334155; text-align: center; }
  .footer p { margin: 0; font-size: 12px; color: #475569; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <span class="header-icon">⚡</span>
    <h1>Charging Session Started!</h1>
    <p>Your 20-minute piezoelectric charging session is now live.</p>
  </div>

  <div class="body">
    <p class="greeting">Hi, <strong style="color:#4ade80">{{ $session->student_name }}</strong>!</p>

    <p class="message">
      Your charging session has begun. Keep walking — every step you take generates piezoelectric energy that charges your device!
    </p>

    <div class="card">
      <div class="card-row">
        <span class="card-label">Student</span>
        <span class="card-value">{{ $session->student_name }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Email</span>
        <span class="card-value">{{ $session->student_email }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Started At</span>
        <span class="card-value">{{ $session->started_at->format('h:i A') }} · {{ $session->started_at->format('F j, Y') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Duration</span>
        <span class="card-value"><span class="badge">20 minutes</span></span>
      </div>
    </div>

    <div class="tip">
      <strong>💡 Tip:</strong> Your session will automatically end after 20 minutes. You'll receive another email when it's done. You can safely close the scan page — your session will continue running.
    </div>
  </div>

  <div class="footer">
    <p>This is an automated message from <strong style="color:#94a3b8">Piezo Dashboard</strong> · SPCC<br>Please do not reply to this email.</p>
  </div>

</div>
</body>
</html>