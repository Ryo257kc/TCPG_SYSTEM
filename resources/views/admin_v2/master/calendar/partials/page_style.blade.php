<style>
    body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
    .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
    .page{max-width:1440px;margin:18px auto}
    .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
    .row{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border:1px solid #d3dff0;padding:6px;white-space:nowrap;text-align:center}
    th{background:#f5f8fd}
    input,select,button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
    button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
    .wrap{overflow:auto;max-height:70vh}
    .text-input{width:180px}
    .value-text{display:inline-block;min-width:120px;color:#1f2937}
    .value-text.empty{color:#9aa4b2}
    .view-field{display:inline-flex;align-items:center;justify-content:center}
    .edit-field{display:none !important}
    tr.is-editing .view-field{display:none !important}
    tr.is-editing .edit-field{display:inline-flex !important;align-items:center;justify-content:center}
    tbody tr:hover td{background:#f7fbff}
    tbody tr:focus-within td{background:#eef5ff}
    .calendar-row.is-company-holiday-row td{background:#fff6eb}
    .calendar-row.is-company-holiday-row:hover td,
    .calendar-row.is-company-holiday-row:focus-within td{background:#fff1dd}
    .inline-actions{display:flex;gap:8px;justify-content:center}
    .btn-edit{background:#fff;color:#1f4f8f}
    .btn-cancel{background:#fff;color:#6b7280}
    .btn-delete{background:#fff;color:#b42318;border-color:#efc2bf}
    .add-form{margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .select-input{min-width:120px;background:#fff}
    .hint{color:#4b5563;font-size:13px}
</style>