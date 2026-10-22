@extends('admin_v2.login.two_factor_layout')

@section('content')
    @if ($enrolling)
        <h1>認証アプリを登録</h1>
        <p>認証アプリでQRコードを読み取り、表示された6桁のコードを入力してください。</p>
        <div class="qr">{!! $qr !!}</div>
        <details>
            <summary>QRコードを読み取れない場合</summary>
            <p>認証アプリで「時間ベース」のアカウントを追加し、次のキーを入力してください。</p>
            <code class="secret">{{ $secret }}</code>
        </details>
        <form method="POST" action="{{ route('admin.two_factor.enroll') }}">
            @csrf
            <label for="code">認証アプリの6桁コード</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
            @if ($canTrust)
                <label class="saved"><input type="checkbox" name="trust_device" value="1"> この端末を30日間信頼する（共用端末では選ばないでください）</label>
            @endif
            <button type="submit">確認して登録</button>
        </form>
    @else
        <h1>二段階認証</h1>
        <p>認証アプリに表示されている6桁のコードを入力してください。</p>
        <form method="POST" action="{{ route('admin.two_factor.verify') }}">
            @csrf
            <label for="code">認証コード</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
            @if ($canTrust)
                <label class="saved"><input type="checkbox" name="trust_device" value="1"> この端末を30日間信頼する（共用端末では選ばないでください）</label>
            @endif
            <button type="submit">認証してログイン</button>
        </form>
        <details>
            <summary>認証アプリを使えない場合</summary>
            <p>保存してあるリカバリーコードを1つ入力してください。使用したコードは再利用できません。</p>
            <form method="POST" action="{{ route('admin.two_factor.verify') }}">
                @csrf
                <label for="recovery_code">リカバリーコード</label>
                <input id="recovery_code" name="recovery_code" type="text" maxlength="21" autocomplete="off" spellcheck="false" required>
                <button type="submit">リカバリーコードでログイン</button>
            </form>
        </details>
    @endif
    <form method="POST" action="{{ route('admin.two_factor.cancel') }}">
        @csrf
        <button class="secondary" type="submit">ログイン画面に戻る</button>
    </form>
@endsection
