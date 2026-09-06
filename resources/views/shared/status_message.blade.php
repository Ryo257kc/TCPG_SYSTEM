{{-- 全ページ共通の状態・エラーメッセージ表示（admin_v2・staff_portal共通）。
     各ページはコントローラー側の実装(セッションflash or 直接変数)を変えずに済むよう、
     どちらの渡し方でも拾えるようにしてある。
     見た目はCSSファイル(app-frame.css/app-shell.css)の.status/.errorに頼らず、
     ここで直接指定する。2つのCSSファイルを毎回揃えて直す手間をなくすため。 --}}
@php
$statusMessageValue = trim((string) ($statusMessage ?? session('status') ?? session('statusMessage') ?? session('successMessage') ?? ''));
$errorMessageValue = trim((string) ($errorMessage ?? session('errorMessage') ?? ''));
$messageBoxStyle = 'padding:9px 10px;border-radius:8px;font-weight:700;font-size:14px;margin:10px 0;';
$statusBoxStyle = $messageBoxStyle . 'border:1px solid #7fb58b;background:#eef9f0;color:#1f6b32;';
$errorBoxStyle = $messageBoxStyle . 'border:1px solid #d66b6b;background:#fff0f0;color:#9b1c1c;';
@endphp
@if ($statusMessageValue !== '')
<div style="{{ $statusBoxStyle }}">{{ $statusMessageValue }}</div>
@endif
@if ($errorMessageValue !== '')
<div style="{{ $errorBoxStyle }}">{{ $errorMessageValue }}</div>
@endif
@if ($errors->any())
<div style="{{ $errorBoxStyle }}">
    <ul style="margin:0;padding-left:18px;">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
