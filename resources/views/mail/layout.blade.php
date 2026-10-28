<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>@yield('title')</title>
{{-- Shared MIDAD email frame. Inline styles carry the light palette (what most clients show); clients that honour
     prefers-color-scheme get the soft dark palette. Tables keep the layout intact in Outlook. --}}
<style>
    body { margin: 0; padding: 0; }
    a { text-decoration: none; }
    @media (max-width: 520px) {
        .m-outer { padding: 20px 10px !important; }
        .m-body { padding: 28px 22px 26px !important; }
        .m-h1 { font-size: 22px !important; }
        .m-button, .m-button a { display: block !important; width: 100% !important; box-sizing: border-box; text-align: center !important; }
        .m-gap { display: none !important; }
        .m-stack { display: block !important; width: 100% !important; margin: 0 0 10px !important; }
        .m-buttons { width: 100% !important; }
    }
    @media (prefers-color-scheme: dark) {
        .m-page, .m-outer { background: #1b1a17 !important; }
        .m-card { background: #22211d !important; border-color: #33312c !important; }
        .m-ink { color: #e6dfd2 !important; }
        .m-soft { color: #bab3a5 !important; }
        .m-stone { color: #9b9487 !important; }
        .m-copper, .m-copper a { color: #c29a79 !important; }
        .m-rule { background: #c29a79 !important; }
        .m-note, .m-verse { background: #292824 !important; border-color: #3a3833 !important; color: #e6dfd2 !important; }
        .m-primary { background: #bdb3a1 !important; }
        .m-primary a { color: #1b1a17 !important; }
        .m-secondary { border-color: #c29a79 !important; }
        .m-secondary a { color: #c29a79 !important; }
        .m-line { border-color: #33312c !important; }
    }
</style>
</head>
<body class="m-page" style="margin:0;padding:0;background:#f5f2eb">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">@yield('preheader')&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
<table role="presentation" class="m-outer" width="100%" cellpadding="0" cellspacing="0" dir="rtl" style="background:#f5f2eb;padding:36px 14px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="rtl" style="max-width:580px">
            {{-- Wordmark --}}
            <tr><td align="center" style="padding:0 0 22px">
                <div class="m-ink" style="color:#24231f;font-family:'Traditional Arabic','Times New Roman',Georgia,serif;font-size:34px;font-weight:bold;line-height:1.2">مِداد</div>
                <div class="m-copper" dir="ltr" style="margin-top:4px;color:#8f5c39;font-family:Arial,sans-serif;font-size:11px;font-weight:bold;letter-spacing:4px">MIDAD</div>
            </td></tr>
            {{-- Card --}}
            <tr><td class="m-card" style="background:#fbf9f4;border:1px solid #e4ded2;border-radius:16px;overflow:hidden">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="rtl">
                    <tr><td class="m-rule" style="height:3px;line-height:3px;font-size:0;background:#8f5c39">&nbsp;</td></tr>
                    <tr><td class="m-body" dir="rtl" style="padding:38px 40px 34px;text-align:right;font-family:'Segoe UI',Tahoma,Arial,sans-serif;color:#24231f">
                        @hasSection('eyebrow')
                            <div class="m-copper" style="margin:0 0 10px;color:#8f5c39;font-size:13px;font-weight:bold">@yield('eyebrow')</div>
                        @endif
                        <h1 class="m-h1 m-ink" style="margin:0 0 14px;color:#24231f;font-size:25px;font-weight:bold;line-height:1.6">@yield('heading')</h1>
                        @yield('content')
                    </td></tr>
                </table>
            </td></tr>
            {{-- Footer --}}
            <tr><td align="center" style="padding:26px 18px 0;font-family:'Segoe UI',Tahoma,Arial,sans-serif">
                <div class="m-copper" style="color:#8f5c39;font-family:'Traditional Arabic','Times New Roman',Georgia,serif;font-size:19px;line-height:1.6">لأن للكلمات أثرًا يبقى</div>
                <div class="m-stone" style="margin-top:8px;color:#6c6860;font-size:12px;line-height:1.8">@yield('footer', 'مِداد · مكتبة عربية للاقتباسات والشعر')</div>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
