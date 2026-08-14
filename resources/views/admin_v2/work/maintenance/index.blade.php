<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - メンテナンス設定</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/information.css') }}">
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">TCPG SYSTEM メンテナンス設定</h1>
        </div>

        <section class="panel information-page-panel">
            @if (session('status'))
            <div class="status_box">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
            <div class="error_box">
                {{ $errors->first() }}
            </div>
            @endif

            <p>指定した時間帯は、システムマスタ以外ログイン不可・既にログイン中の人も次のページ操作で強制ログアウトになります。</p>

            <section class="information-section">
                <div class="information-section-head">
                    <h2 class="sub_title">新規追加</h2>
                </div>

                <form method="post" action="{{ route('admin.work.maintenance.save') }}" class="information-form">
                    @csrf
                    <div class="information-form-grid">
                        <label class="information-field">
                            <span class="information-label">開始日時</span>
                            <input type="datetime-local" name="start_at" value="{{ old('start_at') }}" required>
                        </label>

                        <label class="information-field">
                            <span class="information-label">終了日時</span>
                            <input type="datetime-local" name="end_at" value="{{ old('end_at') }}" required>
                        </label>

                        <label class="information-field information-field-full">
                            <span class="information-label">メッセージ（任意）</span>
                            <input type="text" name="message" value="{{ old('message') }}" maxlength="200" placeholder="未入力ならデフォルトの案内文になります">
                        </label>
                    </div>

                    <div class="information-actions">
                        <button type="submit" class="btn btn-primary">追加</button>
                    </div>
                </form>
            </section>

            <section class="information-section">
                <div class="information-section-head">
                    <h2 class="sub_title">予定一覧</h2>
                    <span class="information-count">{{ count($windows) }}件</span>
                </div>

                <div class="information-list">
                    @forelse ($windows as $window)
                    <div class="information-entry-card">
                        <div class="information-form-grid">
                            <div class="information-field">
                                <span class="information-label">開始</span>
                                <span>{{ \Carbon\Carbon::parse($window['start_at'])->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="information-field">
                                <span class="information-label">終了</span>
                                <span>{{ \Carbon\Carbon::parse($window['end_at'])->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="information-field">
                                <span class="information-label">状態</span>
                                <span>{{ $window['is_active'] ? '実施中' : '' }}</span>
                            </div>
                            @if($window['message'] !== '')
                            <div class="information-field information-field-full">
                                <span class="information-label">メッセージ</span>
                                <span>{{ $window['message'] }}</span>
                            </div>
                            @endif
                        </div>
                        <div class="information-actions">
                            <form method="post" action="{{ route('admin.work.maintenance.destroy', ['maintenanceId' => $window['maintenance_id']]) }}" onsubmit="return confirm('削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn">削除</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="information-empty">予定されているメンテナンスはありません。</div>
                    @endforelse
                </div>
            </section>
        </section>
    </div>
</body>

</html>
