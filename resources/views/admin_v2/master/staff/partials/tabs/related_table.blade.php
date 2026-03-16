<div class="related-card">
  <div class="related-header">
    <h3>{{ $title }}</h3>
    <span>{{ number_format(count($dataset['rows'] ?? [])) }} 件</span>
  </div>
  @if(($dataset['rows'] ?? []) !== [] && ($dataset['columns'] ?? []) !== [])
    <div class="wrap">
      <table>
        <thead>
          <tr>
            @foreach($dataset['columns'] as $column)
              <th>{{ $fieldLabels[$column] ?? $column }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($dataset['rows'] as $row)
            <tr>
              @foreach($dataset['columns'] as $column)
                <td>{{ ($row[$column] ?? '') !== '' ? $row[$column] : '---' }}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="staff-empty">データがありません</div>
  @endif
</div>