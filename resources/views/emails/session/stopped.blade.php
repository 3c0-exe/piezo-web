<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Ended</title>
<style>
  body { margin: 0; padding: 0; background: #0f172a; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 560px; margin: 40px auto; background: #1e293b; border-radius: 16px; overflow: hidden; border: 1px solid #334155; }
  .header { background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%); padding: 36px 40px; text-align: center; }
  .header-icon { font-size: 48px; display: block; margin-bottom: 8px; }
  .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: #bfdbfe; font-size: 14px; }
  .body { padding: 36px 40px; }
  .greeting { font-size: 16px; color: #e2e8f0; margin: 0 0 20px; }
  .message { font-size: 15px; color: #94a3b8; line-height: 1.7; margin: 0 0 28px; }
  .card { background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; }
  .card-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; }
  .card-row:last-child { border-bottom: none; }
  .card-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .card-value { font-size: 14px; color: #e2e8f0; font-weight: 600; }
  .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 28px; }
  .stat-box { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 16px; text-align: center; }
  .stat-box .num { font-size: 22px; font-weight: 700; color: #60a5fa; }
  .stat-box .lbl { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }
  .tip { background: #0c1a2e; border: 1px solid #1d4ed8; border-radius: 10px; padding: 16px 20px; font-size: 13px; color: #93c5fd; line-height: 1.6; }
  .footer { padding: 20px 40px; border-top: 1px solid #334155; text-align: center; }
  .footer p { margin: 0; font-size: 12px; color: #475569; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <span class="header-icon">✅</span>
    <h1>Session Complete!</h1>
    <p>Your 20-minute charging session has officially ended.</p>
  </div>

  <div class="body">
    <p class="greeting">Great job, <strong style="color:#60a5fa">{{ $session->student_name }}</strong>!</p>

    <p class="message">
      Your piezoelectric charging session has ended. Please disconnect your device from the charger so the next student can use it. Thank you for participating!
    </p>

    <div class="stat-grid">
      <div class="stat-box">
        <div class="num">{{ number_format($session->total_steps ?? 0) }}</div>
        <div class="lbl">Total Steps</div>
      </div>
      <div class="stat-box">
        <div class="num">{{ number_format($session->peak_watts ?? 0, 3) }} W</div>
        <div class="lbl">Peak Power</div>
      </div>
      <div class="stat-box">
        <div class="num">{{ $session->battery_start !== null ? $session->battery_start . '%' : '—' }}</div>
        <div class="lbl">Battery Start</div>
      </div>
      <div class="stat-box">
        <div class="num">{{ $session->battery_end !== null ? $session->battery_end . '%' : '—' }}</div>
        <div class="lbl">Battery End</div>
      </div>
    </div>

    <div class="card">
      <div class="card-row">
        <span class="card-label">Started</span>
        <span class="card-value">{{ $session->started_at->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Ended</span>
        <span class="card-value">{{ $session->ended_at?->format('h:i A') ?? now()->format('h:i A') }}</span>
      </div>
      <div class="card-row">
        <span class="card-label">Date</span>
        <span class="card-value">{{ $session->started_at->format('F j, Y') }}</span>
      </div>
    </div>

    <div class="tip">
      <strong style="color:#93c5fd">📌 Reminder:</strong> Please remove your device from the charging pad so other students can take their turn. Thank you for your contribution to the Piezo Energy Project!
    </div>
  </div>

  <div class="footer">
    <p>This is an automated message from <strong style="color:#94a3b8">Piezo Dashboard</strong> · SPCC<br>Please do not reply to this email.</p>
  </div>

</div>
</body>
</html>