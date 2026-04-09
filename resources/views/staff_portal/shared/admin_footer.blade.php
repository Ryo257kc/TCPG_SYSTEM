<section class="panel">
    @if (!empty($isAdmin))
        <h2 style="margin:0 0 8px; font-size:22px; text-align:center;">管理者 MENU</h2>
        <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:nowrap; justify-content:center; overflow-x:auto; padding-bottom:2px;">
            <a class="quick-link" href="{{ route('admin.attendance.index') }}">勤怠管理</a>
            <a class="quick-link" href="{{ route('admin.paid-leave.index') }}">有給申請</a>
            <a class="quick-link" href="{{ route('admin.sales') }}">売上</a>
        </div>
    @endif
    <p style="margin:10px 0 0; text-align:center; color:#8f5d36; font-weight:700;">TOTALCARE Co.,Ltd.</p>
</section>
