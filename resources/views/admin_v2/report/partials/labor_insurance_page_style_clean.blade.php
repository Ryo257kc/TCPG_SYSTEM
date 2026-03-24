<style>
    .filter-panel { padding: 16px 18px; margin-bottom: 14px; }
    .report-filter-grid { display: grid; grid-template-columns: 180px minmax(280px, 1fr) auto; gap: 14px; align-items: end; }
    .field { display: grid; gap: 6px; }
    .field span { font-size: 12px; color: var(--muted); font-weight: 600; }
    .field select { width: 100%; height: 40px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; background: #fff; color: var(--text); }
    .filter-actions { display: flex; gap: 8px; }
    .labor-layout { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
    .summary-panel { padding: 18px; }
    .summary-head h2 { margin: 0; color: var(--primary); font-size: 18px; }
    .summary-head p { margin: 6px 0 0; color: var(--muted); font-size: 12px; }
    .summary-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; margin: 16px 0 0; }
    .summary-list div { display: grid; gap: 4px; }
    .summary-list dt { color: var(--muted); font-size: 12px; }
    .summary-list dd { margin: 0; font-size: 22px; font-weight: 700; color: var(--primary); }
    .compact-list dd { font-size: 18px; }
    .table-panel { padding: 14px 16px 16px; }
    .table-head { margin-bottom: 10px; }
    .table-head h2 { margin: 0; color: var(--primary); font-size: 18px; }
    .table-head p { margin: 6px 0 0; color: var(--muted); font-size: 12px; }
    .table-wrap { overflow-x: auto; }
    .report-table { width: 100%; min-width: 900px; border-collapse: collapse; table-layout: fixed; }
    .report-table th, .report-table td { border: 1px solid var(--line); padding: 8px 10px; vertical-align: middle; background: #fff; }
    .report-table thead th { background: #f7faff; color: var(--primary); font-size: 12px; text-align: center; }
    .labor-table tbody th, .labor-table tfoot th { background: #fbfcfe; text-align: left; white-space: nowrap; width: 128px; }
    .labor-table td { text-align: right; font-variant-numeric: tabular-nums; }
    .cell-pair { display: flex; align-items: baseline; justify-content: flex-end; gap: 8px; white-space: nowrap; }
    .amount { font-weight: 700; }
    .count { color: var(--muted); font-size: 11px; }
    .total-cell { background: #f8fbff !important; color: var(--primary); }
    .total-cell .amount { color: var(--primary); }
    .is-bonus th { color: #93540f; }
    .empty-cell { text-align: center !important; color: var(--muted); padding: 24px 12px !important; }
    .transfer-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 16px; }
    .transfer-card { border: 1px solid var(--line); border-radius: 12px; background: #fbfdff; padding: 14px 16px; }
    .transfer-card h3 { margin: 0 0 10px; color: var(--primary); font-size: 15px; }
    .transfer-card dl { display: grid; gap: 10px; margin: 0; }
    .transfer-card dl div { display: flex; justify-content: space-between; gap: 16px; }
    .transfer-card dt { color: var(--muted); font-size: 12px; }
    .transfer-card dd { margin: 0; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; }
    .formula-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .formula-card { border: 1px solid var(--line); border-radius: 12px; background: #fbfdff; padding: 14px 16px; }
    .formula-card h3 { margin: 0 0 10px; color: var(--primary); font-size: 15px; }
    .formula-list { margin: 0; padding-left: 18px; display: grid; gap: 8px; color: var(--text); }
    .formula-list li { line-height: 1.5; }
    @media (max-width: 980px) {
        .report-filter-grid, .labor-layout, .transfer-grid, .formula-grid { grid-template-columns: 1fr; }
        .report-table { min-width: 820px; }
        .cell-pair { gap: 6px; }
    }
</style>
