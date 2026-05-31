<!DOCTYPE html>
<html lang="{{ $receiver?->preferred_language ?? 'en' }}" dir="{{ ($receiver?->preferred_language ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $platformName }} - New Appreciation</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 28px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.85); margin: 8px 0 0; }
        .star { font-size: 48px; margin-bottom: 16px; display: block; }
        .body { padding: 40px 32px; }
        .greeting { font-size: 18px; color: #1f2937; margin-bottom: 24px; }
        .message-box { background: #f0f4ff; border-left: 4px solid #6366f1; border-radius: 8px; padding: 20px; margin: 24px 0; }
        .message-box p { margin: 0; color: #374151; font-size: 16px; line-height: 1.6; font-style: italic; }
        .sender-info { display: flex; align-items: center; gap: 16px; margin: 24px 0; padding: 16px; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb; }
        .sender-name { font-weight: 700; color: #111827; font-size: 16px; }
        .sender-title { color: #6b7280; font-size: 14px; }
        .cta { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #6366f1; color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; }
        .footer { background: #f9fafb; padding: 24px 32px; text-align: center; color: #9ca3af; font-size: 13px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="star">⭐</span>
        <h1>{{ $platformName }}</h1>
        <p>You received an appreciation!</p>
    </div>

    <div class="body">
        <p class="greeting">
            Hello <strong>{{ $receiver?->full_name ?? 'Colleague' }}</strong>,
        </p>
        <p style="color:#374151;">Great news! Your colleague <strong>{{ $sender?->full_name ?? 'Someone' }}</strong> has sent you an appreciation.</p>

        @if($appreciation->message)
        <div class="message-box">
            <p>"{{ $appreciation->message }}"</p>
        </div>
        @else
        <div class="message-box">
            <p>⭐ A star appreciation — no words needed!</p>
        </div>
        @endif

        <div class="sender-info">
            <div>
                <div class="sender-name">{{ $sender?->full_name }}</div>
                <div class="sender-title">{{ $sender?->job_title }} • {{ $sender?->department?->name }}</div>
            </div>
        </div>

        <div class="cta">
            <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/dashboard" class="btn">
                View Your Dashboard
            </a>
        </div>

        <p style="color:#9ca3af;font-size:13px;text-align:center;">
            Keep up the great work! Your contributions are valued and recognized.
        </p>
    </div>

    <div class="footer">
        <p>{{ $platformName }} • Internal Employee Recognition Platform</p>
        <p>You received this email because you are a registered employee.</p>
    </div>
</div>
</body>
</html>
