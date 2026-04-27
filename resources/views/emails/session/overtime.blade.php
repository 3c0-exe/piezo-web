<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Overtime</title>
<style>
  body { margin: 0; padding: 0; background: #0f172a; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 560px; margin: 40px auto; background: #1e293b; border-radius: 16px; overflow: hidden; border: 1px solid #334155; }
  .header { background: linear-gradient(135deg, #78350f 0%, #d97706 100%); padding: 36px 40px; text-align: center; }
  .header-icon { font-size: 48px; display: block; margin-bottom: 8px; }
  .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: #fef3c7; font-size: 14px; }
  .body { padding: 36px 40px; }
  .greeting { font-size: 16px; color: #e2e8f0; margin: 0 0 20px; }
  .message { font-size: 15px; color: #94a3b8; line-height: 1.7; margin: 0 0 28px; }
  .card { background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; }
  .card-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; }
  .card-row:last-child { border-bottom: none; }
  .card-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .card-value { font-size: 14px; color: #e2e8f0; font-weight: 600; }
  .overtime-badge { display: inline-block; background: #92400e; color: #fcd34d; font-size: 13px; font-weight: 700; padding: 6px 16px; border-radius: 99px; border: 1px solid #d97706; }
  .tip { background: #1c1000; border: 1px solid #92400e; border-radius: 10px; padding: 16px 20px; font-size: 13px; color: #fcd34d; line-height: 1.6; }
  .footer { padding: 20px 40px; border-top: 1px solid #334155; text-align: center; }
  .footer p { margin: 0; font-size: 12px; color: #475569; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <span class="header-icon">⏰</span>
    <h1>Session Overtime!</h1>
    <p>Your 20-minute session ended — please disconnect now.</p>
  </div>

  <div class="body">
    <p class="greeting">Hey, <strong style="color:#fbbf24">{{ $session->student_name }}</strong>!</p>

    <p class="message">
      Your charging session officially ended at the 20-minute mark, but it looks like it's still running. Please disconnect your device from the charging pad so other students can have their turn. We appreciate your cooperation!
    </p>

    <div class="card">
      <div class="card-row">
        <span class="card-label">Started</span>
        <span class="card-value">{{ $session->started_at->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Should Have Ended</span>
        <span class="card-value">{{ $session->started_at->addMinutes(20)->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Status</span>
        <span class="card-value"><span class="overtime-badge">⚠ OVERTIME</span></span>
      </div>
    </div>

    <div class="tip">
      <strong>🙏 Please:</strong> Disconnect your device now and allow the next student to use the Piezo charger. Every student deserves their fair share of charging time. Thank you for being considerate!
    </div>
  </div>

  <div class="footer">
    <p>This is an automated message from <strong style="color:#94a3b8">Piezo Dashboard</strong> · SPCC<br>Please do not reply to this email.</p>
  </div>

</div>
</body>
</html>