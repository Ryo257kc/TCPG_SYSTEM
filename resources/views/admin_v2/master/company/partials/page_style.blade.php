<style>
body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
.page{max-width:1440px;margin:18px auto}
.card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
.btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
.toolbar{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.toolbar h2{margin:0;color:#123c73}
.toolbar-links{display:flex;gap:8px;flex-wrap:wrap}
.filter-form{margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.filter-form input,.filter-form button,.company-detail-form input,.company-detail-form button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px;box-sizing:border-box}
.filter-form input{min-width:360px}
.filter-form button,.company-detail-form button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
.meta{margin:10px 0 0;color:#46658b;font-size:13px}
.status{margin:10px 0 0;padding:10px 12px;border-radius:10px;background:#edf7ed;border:1px solid #c9e3c9;color:#25603b}
.company-layout{display:grid;grid-template-columns:320px minmax(0,1fr);gap:16px;margin-top:14px;align-items:start}
.company-list-panel,.company-detail-panel{border:1px solid #d3dff0;border-radius:14px;background:#fdfefe;overflow:hidden}
.panel-title{padding:12px 14px;background:#f5f8fd;border-bottom:1px solid #d3dff0;color:#123c73;font-weight:700}
.company-list{max-height:70vh;overflow:auto}
.company-list-item{display:block;padding:12px 14px;border-bottom:1px solid #e5edf8;text-decoration:none;color:#1f2937;background:#fff}
.company-list-item:hover{background:#f8fbff}
.company-list-item-active{background:#e8f1ff}
.company-list-main{display:flex;gap:8px;align-items:center}
.company-list-code{font-weight:700;color:#123c73;min-width:48px}
.company-list-name{font-weight:600}
.company-list-sub{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:6px;font-size:12px;color:#5b708f}
.company-detail-form{padding:14px}
.company-detail-panel.editing .company-view{display:none}
.company-detail-panel:not(.editing) .company-edit{display:none}
.company-tab-bar{display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid #d3dff0;background:#fbfdff}
.company-tab{display:inline-flex;padding:7px 12px;border:1px solid #c9d7eb;border-radius:999px;background:#fff;color:#46658b;font-weight:700;cursor:pointer}
.company-tab.is-active{background:#1f4f8f;border-color:#1f4f8f;color:#fff}
.company-tab-panels{padding:14px}
.company-tab-panel{display:none}
.company-tab-panel.is-active{display:block}
.company-tab-readonly{padding:0}
.current-rate-card{margin-top:18px;padding:14px;border:1px solid #d3dff0;border-radius:12px;background:#fff}
.current-rate-card .current-rate-edit{display:none}
.current-rate-card .current-rate-create{display:none}
.current-rate-card.editing .current-rate-view{display:none}
.current-rate-card.editing .current-rate-edit{display:block}
.current-rate-card.creating .current-rate-view{display:none}
.current-rate-card.creating .current-rate-create{display:block}
.current-rate-meta{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.current-rate-meta-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px}
.current-rate-meta-edit{margin-top:12px}
.current-rate-meta-edit-grid .current-rate-meta-item input{width:100%}
.current-rate-meta-item{padding:10px 12px;border:1px solid #dbe6f5;border-radius:10px;background:#f8fbff}
.current-rate-meta-item span{display:block;font-size:12px;color:#5b708f;margin-bottom:4px}
.current-rate-meta-item strong{font-size:15px;color:#123c73}
.current-rate-summary{display:grid;grid-template-columns:minmax(0,1fr) 180px;gap:12px;align-items:start}
.current-rate-summary-single{grid-template-columns:1fr}
.current-rate-table{width:100%;border-collapse:collapse;border:1px solid #d3dff0;border-radius:10px;overflow:hidden;background:#fff}
.current-rate-table th,.current-rate-table td{padding:10px 12px;border-bottom:1px solid #e5edf8;font-size:13px;text-align:left}
.current-rate-table thead th{background:#f5f8fd;color:#46658b;font-weight:700}
.current-rate-table tbody th{background:#fbfdff;color:#123c73;font-weight:700;white-space:nowrap}
.current-rate-table-edit input{width:100%;min-width:0}
.current-rate-sidecard{padding:14px;border:1px solid #dbe6f5;border-radius:10px;background:#f8fbff}
.current-rate-sidecard span{display:block;font-size:12px;color:#5b708f;margin-bottom:6px}
.current-rate-sidecard strong{display:block;font-size:22px;color:#123c73;line-height:1.2}
.current-rate-sidecard-edit input{width:100%}
.rate-history{margin-top:18px}
.rate-history-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;color:#46658b}
.rate-history-header h3{margin:0;font-size:14px;color:#123c73}
.rate-table-wrap{overflow:auto;border:1px solid #d3dff0;border-radius:10px;background:#fff}
.rate-table{width:100%;border-collapse:collapse;min-width:980px}
.rate-table th,.rate-table td{padding:8px 10px;border-bottom:1px solid #e5edf8;font-size:13px;text-align:left;white-space:nowrap}
.rate-table th{background:#f5f8fd;color:#46658b;font-weight:700}
.rate-table .action-cell{white-space:nowrap}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.detail-field{display:flex;flex-direction:column;gap:6px}
.detail-field span{font-size:13px;color:#46658b;font-weight:600}
.detail-field-wide{grid-column:1 / -1}
.detail-field input{width:100%}
.detail-field input[disabled]{background:#f7f9fc;color:#6d7f97}
.detail-section{padding:8px 10px;border:1px solid #c8d9f0;border-radius:10px;background:#eaf2ff;margin-top:6px}
.detail-section span{display:block;font-size:13px;color:#123c73;font-weight:800;letter-spacing:.02em}
.detail-value{min-height:36px;padding:8px 10px;border:1px solid #d3dff0;border-radius:8px;background:#f7f9fc;color:#1f2937;box-sizing:border-box}
.detail-value-empty{color:#8ca0ba}
.detail-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
.detail-actions-spread{justify-content:space-between;align-items:center}
.detail-actions-right{display:flex;justify-content:flex-end;gap:8px}
.btn-secondary{display:inline-flex;padding:6px 10px;border:1px solid #b7ccef;border-radius:8px;background:#f5f8fd;color:#1f4f8f;text-decoration:none;font-weight:700;cursor:pointer}
.btn-danger-soft{border-color:#efc3c3;background:#fff5f5;color:#9f2f2f}
.company-empty{padding:18px;color:#5b708f}
.mayor-row-edit{display:none;background:#fbfdff}
.mayor-row.editing + .mayor-row-edit{display:table-row}
.mayor-row-edit td{background:#fbfdff}
.mayor-inline-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.mayor-inline-grid .detail-field-wide{grid-column:1 / -1}
@media (max-width: 980px){.company-layout{grid-template-columns:1fr}.company-list{max-height:none}.detail-grid{grid-template-columns:1fr}.current-rate-summary{grid-template-columns:1fr}.current-rate-meta-grid{grid-template-columns:1fr}.detail-actions-spread{flex-direction:column;align-items:stretch}.detail-actions-right{justify-content:stretch}.detail-actions-right button{flex:1}}
</style>
