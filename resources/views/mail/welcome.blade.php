<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>أهلًا بك في مِداد</title>
{{-- Inline styles are the light palette (what most clients show); clients that honour prefers-color-scheme get the soft dark palette below. --}}
<style>
    @media (prefers-color-scheme: dark) {
        .m-page { background: #1b1a17 !important; }
        .m-card { background: #22211d !important; border-color: #33312c !important; }
        .m-ink { color: #e6dfd2 !important; }
        .m-soft { color: #bab3a5 !important; }
        .m-copper { color: #c29a79 !important; }
        .m-rule { background: #c29a79 !important; }
        .m-verse { background: #292824 !important; border-color: #6a5545 !important; color: #e6dfd2 !important; }
        .m-primary { background: #bdb3a1 !important; }
        .m-primary a { color: #1b1a17 !important; }
        .m-secondary { border-color: #c29a79 !important; }
        .m-secondary a { color: #c29a79 !important; }
        .m-line { border-color: #33312c !important; }
    }
    @media (max-width: 480px) {
        .m-buttons { width: 100% !important; }
        .m-button { display: block !important; width: 100% !important; box-sizing: border-box; margin: 0 0 10px !important; text-align: center !important; }
        .m-button a { display: block !important; }
        .m-spacer { display: none !important; }
    }
</style>
</head>
<body class="m-page" style="margin:0;padding:0;background:#f5f2eb">
<div style="display:none;max-height:0;overflow:hidden;opacity:0">أهلًا بك في مِداد — لأن للكلمات أثرًا يبقى.</div>
<div dir="rtl" class="m-page" style="margin:0;background:#f5f2eb;padding:32px 14px;font-family:Tahoma,Arial,sans-serif;color:#24231f;line-height:1.9;text-align:right">
    <table role="presentation" class="m-card" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;border:1px solid #ded9ce;background:#fbf9f4;border-collapse:separate">
        <tr><td style="padding:30px 28px 6px;text-align:center">
            <div class="m-copper" style="color:#8f5c39;font-size:13px;font-weight:bold;letter-spacing:.4px">مِداد | <span dir="ltr">MIDAD</span></div>
            <div class="m-rule" style="width:42px;height:1px;margin:16px auto 0;background:#8f5c39"></div>
        </td></tr>
        <tr><td style="padding:12px 28px 4px">
            <h1 class="m-ink" style="margin:10px 0 12px;color:#24231f;font-size:24px;font-weight:600;line-height:1.6;text-align:center">أهلًا بك يا {{ $name }}</h1>
            <p class="m-soft" style="margin:0 0 22px;color:#66635c;font-size:15px;text-align:center">يسعدنا انضمامك إلى مِداد، المكتبة العربية التي نحفظ فيها الكلمات التي تترك فينا أثرًا. تصفّح ما شاركه القرّاء، واحفظ ما يلامسك، وشارك اقتباسك الأول حين تجد الكلمة التي تشبهك.</p>
        </td></tr>
        <tr><td style="padding:0 28px">
            <div class="m-verse" style="margin:0 0 6px;padding:18px 16px;border:1px solid #d3b79e;border-right:3px solid #8f5c39;background:#f5f2eb;color:#24231f;font-family:'Traditional Arabic','Times New Roman',serif;font-size:21px;line-height:2;text-align:center">«أهلًا بمَن جاءَ الحروفَ مُحِبًّا … فالحرفُ بيتٌ والمِدادُ رِحابُ»</div>
        </td></tr>
        <tr><td style="padding:22px 28px 6px;text-align:center">
            <table role="presentation" class="m-buttons" cellpadding="0" cellspacing="0" style="margin:0 auto"><tr>
                <td class="m-button m-primary" style="background:#24231f;padding:0">
                    <a href="{{ $browseUrl }}" style="display:inline-block;padding:13px 24px;color:#fbf9f4;text-decoration:none;font-size:14px;font-weight:bold">تصفّح الاقتباسات</a>
                </td>
                <td class="m-spacer" style="width:10px;font-size:0">&nbsp;</td>
                <td class="m-button m-secondary" style="border:1px solid #8f5c39;padding:0">
                    <a href="{{ $shareUrl }}" style="display:inline-block;padding:12px 22px;color:#8f5c39;text-decoration:none;font-size:14px;font-weight:bold">+ شارك أول اقتباس لك</a>
                </td>
            </tr></table>
        </td></tr>
        <tr><td style="padding:20px 28px 28px">
            <p class="m-copper m-line" style="margin:12px 0 0;padding-top:16px;border-top:1px solid #ded9ce;color:#8f5c39;font-size:15px;text-align:center">لأن للكلمات أثرًا يبقى</p>
            <p class="m-soft" style="margin:8px 0 0;color:#66635c;font-size:12px;text-align:center">وصلتك هذه الرسالة لأنك أنشأت حسابًا في مِداد بهذا البريد.</p>
        </td></tr>
    </table>
</div>
</body>
</html>
