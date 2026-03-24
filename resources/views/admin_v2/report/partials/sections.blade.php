<div class="report-grid">
    @foreach ($categories as $category)
        <section class="panel category-panel">
            <div class="category-head">
                <h2>{{ $category['title'] }}</h2>
                <p>{{ $category['description'] }}</p>
            </div>

            <div class="report-cards">
                @foreach ($category['items'] as $item)
                    <article class="report-card {{ $item['status'] === 'available' ? 'is-available' : 'is-planned' }}">
                        <div class="report-card-top">
                            <h3>{{ $item['title'] }}</h3>
                            <span class="badge {{ $item['status'] === 'available' ? 'badge-ready' : 'badge-soon' }}">
                                {{ $item['status'] === 'available' ? '利用可' : '準備中' }}
                            </span>
                        </div>
                        <p class="report-desc">{{ $item['description'] }}</p>

                        <div class="report-actions">
                            @if ($item['status'] === 'available')
                                <a class="btn btn-primary" href="{{ $item['url'] }}">{{ $item['action'] ?? '開く' }}</a>
                            @else
                                <span class="btn is-disabled">これから追加</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
