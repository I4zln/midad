{{-- What only the account's owner is likely to know, checked before a reset link is sent. Never read these out to the person asking. --}}
<dl class="admin-verify-list">
    <div><dt>رقم الاستعادة</dt><dd>@if ($phone)<bdi dir="ltr">{{ $phone }}</bdi> <span class="admin-verify-strong">الأوثق: أرسل الرابط إلى هذا الرقم فقط</span>@else<span class="admin-verify-none">لم يُضف رقمًا، فاسأله عمّا يلي دون أن تذكره له</span>@endif</dd></div>
    <div><dt>اسم المستخدم</dt><dd>@if ($username)<bdi dir="ltr">{{ '@'.$username }}</bdi>@else<span class="admin-verify-none">لا يوجد</span>@endif</dd></div>
    <div><dt>انضم في</dt><dd>{{ $joined }} · {{ $quotes }} اقتباس</dd></div>
    <div><dt>آخر اقتباس نشره</dt><dd>@if ($latestQuote)«{{ $latestQuote }}»@else<span class="admin-verify-none">لم ينشر بعد</span>@endif</dd></div>
</dl>
