<section class="panel app-header-panel">
    <div class="header-row">
        <h1>TCPG SYSTEM</h1>
        <p class="welcome-name">
            @if (($showWelcomePrefix ?? true))
                ようこそ、
            @endif
            {{ $displayName }} さん
        </p>
        <nav class="quick-menu" aria-label="header-menu">
            <a class="quick-link" href="{{ route('dashboard') }}">TOP</a>
            <a class="quick-link" href="{{ route('attendance.monthly') }}">{{ $attendanceLabel ?? '月間勤怠' }}</a>
            <a class="quick-link" href="{{ route('mypage') }}">MyPage</a>
        </nav>
        <a class="quick-link logout" href="{{ route('login.portal') }}">ログアウト</a>
    </div>
</section>
