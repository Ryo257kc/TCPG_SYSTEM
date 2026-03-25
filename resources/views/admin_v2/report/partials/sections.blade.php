<div class="report-grid">
    @foreach ($categories as $category)
        <section class="panel category-panel">
            <div class="category-head">
                <h2>{{ $category['title'] }}</h2>
                <p>{{ $category['description'] }}</p>
            </div>

            <div class="report-cards">
                @foreach ($category['items'] as $item)
                    @php
                        $isSocialInsuranceCard = $loop->parent->index === 0 && $loop->index === 2;
                        $isLaborInsuranceCard = $loop->parent->index === 2 && $loop->index === 0;
                        $isAvailable = $isLaborInsuranceCard
                            ? true
                            : ($isSocialInsuranceCard ? false : (($item['status'] ?? '') === 'available'));
                        $itemUrl = $isLaborInsuranceCard
                            ? route('admin.report.labor-insurance.index')
                            : ($isSocialInsuranceCard ? null : ($item['url'] ?? null));
                        $itemAction = $item['action'] ?? '開く';
                    @endphp
                    <article class="report-card {{ $isAvailable ? 'is-available' : 'is-planned' }}">
                        <div class="report-card-top">
                            <h3>{{ $item['title'] }}</h3>
                            <span class="badge {{ $isAvailable ? 'badge-ready' : 'badge-soon' }}">
                                {{ $isAvailable ? '利用可' : '準備中' }}
                            </span>
                        </div>
                        <p class="report-desc">{{ $item['description'] }}</p>

                        <div class="report-actions">
                            @if ($isAvailable && $itemUrl)
                                <a class="btn btn-primary" href="{{ $itemUrl }}">{{ $itemAction }}</a>
                            @else
                                <span class="btn is-disabled">これから追加</span>
                            @endif
                        </div>
                    </article>
                @endforeach

                @if ($loop->index === 1)
                    <article class="report-card is-available">
                        <div class="report-card-top">
                            <h3>離職票確認</h3>
                            <span class="badge badge-ready">利用可</span>
                        </div>
                        <p class="report-desc">離職日基準の月別期間と基礎日数・賃金額を確認する入力補助画面です。</p>
                        <div class="report-actions">
                            <a class="btn btn-primary" href="{{ route('admin.report.rishoku.index') }}">開く</a>
                        </div>
                    </article>
                @endif
            </div>
        </section>
    @endforeach
</div>
