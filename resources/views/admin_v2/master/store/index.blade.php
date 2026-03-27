<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 店舗マスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/store.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 店舗マスタ</div>
    </div>
    <section class="panel">
        <form method="get" class="filter-form">
            <input type="text" name="q" value="{{ $keyword }}" placeholder="店舗コード・店舗名・会社名・連絡先で検索">
            @if($selectedStoreCode !== '')
                <input type="hidden" name="store_code" value="{{ $selectedStoreCode }}">
            @endif
            <button type="submit">表示</button>
        </form>

        <div class="store-layout">
            <div class="store-list-panel">
                <div class="panel-title">店舗一覧</div>
                <div class="store-list">
                    @forelse($rows as $row)
                        <a
                            class="store-list-item {{ $selectedStoreCode === $row['store_code'] ? 'store-list-item-active' : '' }}"
                            href="{{ route('admin.master.store', ['q' => $keyword, 'store_code' => $row['store_code']]) }}"
                        >
                            <div class="store-list-main">
                                <span class="store-list-code">{{ $row['store_code'] }}</span>
                                <span class="store-list-name">{{ $row['store_name'] !== '' ? $row['store_name'] : '店舗名未設定' }}</span>
                            </div>
                            <div class="store-list-sub">
                                <span>{{ $row['company_name'] !== '' ? $row['company_name'] : '会社未設定' }}</span>
                                @if((int) ($row['is_closed'] ?? 0) === 1)
                                    <span class="store-list-badge">閉店</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="store-empty">データがありません</div>
                    @endforelse
                </div>
            </div>

            <div class="store-detail-panel" id="store-detail-panel">
                <div class="panel-title">店舗詳細</div>
                @if($selectedRow)
                    <form method="post" action="{{ route('admin.master.store.update') }}" class="store-detail-form">
                        @csrf
                        <input type="hidden" name="store_code" value="{{ $selectedRow['store_code'] }}">
                        <input type="hidden" name="q" value="{{ $keyword }}">

                        <div class="detail-grid">
                            <label class="detail-field">
                                <span>店舗コード</span>
                                <div class="store-view detail-value">{{ $selectedRow['store_code'] !== '' ? $selectedRow['store_code'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" value="{{ $selectedRow['store_code'] }}" disabled>
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>店舗名</span>
                                <div class="store-view detail-value {{ $selectedRow['store_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_name'] !== '' ? $selectedRow['store_name'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="store_name" value="{{ $selectedRow['store_name'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>業種</span>
                                <div class="store-view detail-value {{ $selectedRow['business_type'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['business_type'] !== '' ? $selectedRow['business_type'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="business_type" value="{{ $selectedRow['business_type'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>訪問エリア</span>
                                <div class="store-view detail-value {{ $selectedRow['visit_area'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['visit_area'] !== '' ? $selectedRow['visit_area'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="visit_area" value="{{ $selectedRow['visit_area'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>会社</span>
                                <div class="store-view detail-value {{ $selectedRow['company_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_name'] !== '' ? $selectedRow['company_name'] : '未設定' }}</div>
                                <div class="store-edit">
                                    <select name="company_id">
                                        <option value="">(未設定)</option>
                                        @foreach($companyOptions as $company)
                                            <option value="{{ $company['company_id'] }}" @selected($company['company_id'] === $selectedRow['company_id'])>{{ $company['company_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>略称</span>
                                <div class="store-view detail-value {{ $selectedRow['store_short_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_short_name'] !== '' ? $selectedRow['store_short_name'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="store_short_name" value="{{ $selectedRow['store_short_name'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>郵便番号</span>
                                <div class="store-view detail-value {{ $selectedRow['postal_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['postal_code'] !== '' ? $selectedRow['postal_code'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="postal_code" value="{{ $selectedRow['postal_code'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>住所カナ</span>
                                <div class="store-view detail-value {{ $selectedRow['address_kana'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['address_kana'] !== '' ? $selectedRow['address_kana'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="address_kana" value="{{ $selectedRow['address_kana'] }}">
                                </div>
                            </label>
                            <label class="detail-field detail-field-wide">
                                <span>店舗住所</span>
                                <div class="store-view detail-value {{ $selectedRow['store_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_address'] !== '' ? $selectedRow['store_address'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="store_address" value="{{ $selectedRow['store_address'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>区分</span>
                                <div class="store-view detail-value {{ $selectedRow['category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['category'] !== '' ? $selectedRow['category'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="category" value="{{ $selectedRow['category'] }}">
                                </div>
                            </label>
                            <label class="detail-field">
                                <span>電話</span>
                                <div class="store-view detail-value {{ $selectedRow['phone'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['phone'] !== '' ? $selectedRow['phone'] : '---' }}</div>
                                <div class="store-edit">
                                    <input type="text" name="phone" value="{{ $selectedRow['phone'] }}">
                                </div>
                            </label>
                            <label class="detail-field detail-field-check">
                                <span>閉店</span>
                                <div class="store-view detail-value">{{ (int) ($selectedRow['is_closed'] ?? 0) === 1 ? '閉店' : '営業中' }}</div>
                                <div class="store-edit detail-check-wrap">
                                    <input type="hidden" name="is_closed" value="0">
                                    <input type="checkbox" name="is_closed" value="1" @checked((int) ($selectedRow['is_closed'] ?? 0) === 1)>
                                </div>
                            </label>
                        </div>

                        <div class="detail-actions">
                            <button type="button" class="btn-secondary store-view" onclick="toggleStoreEdit(true)">編集</button>
                            <button type="submit" class="store-edit">保存</button>
                            <button type="button" class="btn-secondary store-edit" onclick="toggleStoreEdit(false)">取消</button>
                        </div>
                    </form>
                @else
                    <div class="store-empty">表示対象の店舗がありません</div>
                @endif
            </div>
        </div>
    </section>
</div>
@include('admin_v2.master.store.page_script')
</body>
</html>
