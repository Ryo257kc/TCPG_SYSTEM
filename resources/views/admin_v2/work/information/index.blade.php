<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - インフォメーション</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/information.css') }}">
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">TCPG SYSTEM インフォメーション</h1>
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

            <section class="information-section">
                <div class="information-section-head">
                    <h2 class="sub_title">新規追加</h2>
                </div>

                <form method="post" action="{{ route('admin.work.information.save') }}" class="information-form">
                    @csrf
                    <div class="information-form-grid">
                        <label class="information-field">
                            <span class="information-label">日付</span>
                            <input type="date" name="mase_date" value="{{ old('mase_date', $defaultDate) }}">
                        </label>

                        <label class="information-field">
                            <span class="information-label">公開先</span>
                            <input type="text" name="destination" list="information-destination-options" value="{{ old('destination', 'info') }}">
                        </label>

                        <label class="information-field">
                            <span class="information-label">タイトル</span>
                            <input type="text" name="title" value="{{ old('title', '') }}" maxlength="20">
                        </label>

                        <label class="information-field information-check-field">
                            <span class="information-label">公開状態</span>
                            <span class="information-check-wrap">
                                <input type="checkbox" name="me_check" value="1" @checked(old('me_check'))>
                                <span>非公開</span>
                            </span>
                        </label>

                        <label class="information-field information-field-full">
                            <span class="information-label">内容</span>
                            <textarea name="contents" rows="4">{{ old('contents', '') }}</textarea>
                        </label>
                    </div>

                    <div class="information-actions">
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </section>

            <section class="information-section">
                <div class="information-section-head">
                    <h2 class="sub_title">一覧</h2>
                    <span class="information-count">{{ count($messages) }}件</span>
                </div>

                <div class="information-list">
                    @forelse ($messages as $message)
                    <form method="post" action="{{ route('admin.work.information.save') }}" class="information-entry-card">
                        @csrf
                        <input type="hidden" name="mesa_no" value="{{ $message['mesa_no'] }}">

                        <div class="information-form-grid">
                            <label class="information-field">
                                <span class="information-label">日付</span>
                                <input type="date" name="mase_date" value="{{ $message['mase_date'] }}">
                            </label>

                            <label class="information-field">
                                <span class="information-label">公開先</span>
                                <input type="text" name="destination" list="information-destination-options" value="{{ $message['destination'] }}">
                            </label>

                            <label class="information-field">
                                <span class="information-label">タイトル</span>
                                <input type="text" name="title" value="{{ $message['title'] }}" maxlength="20">
                            </label>

                            <label class="information-field information-check-field">
                                <span class="information-label">公開状態</span>
                                <span class="information-check-wrap">
                                    <input type="checkbox" name="me_check" value="1" @checked($message['me_check'])>
                                    <span>{{ $message['me_check'] ? '非公開' : '公開' }}</span>
                                </span>
                            </label>

                            <label class="information-field information-field-full">
                                <span class="information-label">内容</span>
                                <textarea name="contents" rows="4">{{ $message['contents'] }}</textarea>
                            </label>
                        </div>

                        <div class="information-actions">
                            <div class="information-meta">
                                <span>No.{{ $message['mesa_no'] }}</span>
                                <span>{{ $message['mase_date_label'] }}</span>
                            </div>
                            <button type="submit" class="btn btn-primary">保存</button>
                        </div>
                    </form>
                    @empty
                    <div class="information-empty">データがありません。</div>
                    @endforelse
                </div>
            </section>

            <datalist id="information-destination-options">
                @foreach ($destinationOptions as $destinationOption)
                <option value="{{ $destinationOption }}"></option>
                @endforeach
            </datalist>
        </section>
    </div>
</body>

</html>
