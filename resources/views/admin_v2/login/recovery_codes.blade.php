@extends('admin_v2.login.two_factor_layout')

@section('content')
    <h1>リカバリーコードを保存</h1>
    <p>端末を紛失したときに使うコードです。それぞれ1回だけ使えます。印刷するか、認証アプリの端末とは別の安全な場所に保存してください。</p>
    <ul class="codes">
        @foreach ($codes as $code)
            <li>{{ $code }}</li>
        @endforeach
    </ul>
    <p>この画面を完了すると、コードは再表示できません。</p>
    <form method="POST" action="{{ route('admin.two_factor.continue') }}">
        @csrf
        <label class="saved"><input type="checkbox" name="saved" value="1" required> リカバリーコードを保存しました</label>
        <button type="submit">管理画面へ進む</button>
    </form>
@endsection
