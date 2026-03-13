<div class="daily-empty">
    <div class="daily-empty-title">&#26085;&#21029;&#12487;&#12540;&#12479;&#12364;&#35211;&#12388;&#12363;&#12426;&#12414;&#12379;&#12435;</div>
    <p class="daily-empty-text">
        selected staff: {{ $detailStaff['staff_id'] ?? '' }} {{ $detailStaff['staff_name'] ?? '' }}
    </p>
    <p class="daily-empty-text">
        current screen selection uses <code>staff_id</code>, but daily lookup queries <code>mx_time_cards.staff_name</code>.
    </p>
    <p class="daily-empty-text">
        if that column stores the ID for this month, the daily section stays empty even when monthly rows exist.
    </p>
</div>