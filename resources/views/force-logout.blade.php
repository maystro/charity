<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الخروج — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f4f6f8;
            color: #1a1a1a;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: 0 4px 24px rgb(0 0 0 / 8%);
        }
        h1 { margin: 0 0 0.5rem; font-size: 1.25rem; }
        p { margin: 0 0 1.25rem; font-size: 0.9rem; line-height: 1.6; color: #555; }
        .actions { display: flex; flex-direction: column; gap: 0.75rem; }
        a, button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .primary { background: #2563eb; color: #fff; }
        .primary:hover { background: #1d4ed8; }
        .secondary { background: #f3f4f6; color: #374151; }
        .hint { margin-top: 1rem; font-size: 0.75rem; color: #888; }
        code { font-size: 0.8rem; direction: ltr; unicode-bidi: embed; }
    </style>
</head>
<body>
    <div class="card">
        <h1>تسجيل الخروج</h1>
        <p>
            إذا تعذّر الخروج من القائمة (مثلاً بسبب خطأ في قاعدة البيانات أو المايجراشن)،
            استخدم الزر أدناه لإنهاء الجلسة الحالية.
        </p>
        <div class="actions">
            <a href="{{ route('force-logout.now') }}" class="primary">تسجيل الخروج الآن</a>
            <a href="{{ route('login') }}" class="secondary">العودة لتسجيل الدخول</a>
        </div>
        <p class="hint">
            الرابط المباشر: <code>{{ url('/force-logout') }}</code>
        </p>
    </div>
</body>
</html>
