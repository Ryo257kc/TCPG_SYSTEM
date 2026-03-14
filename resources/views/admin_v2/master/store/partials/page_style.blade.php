<style>
body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px}
.page{max-width:1440px;margin:18px auto}
.card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
.btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
.toolbar{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;align-items:center}
.toolbar h2{margin:0;color:#123c73}
.toolbar-links{display:flex;gap:8px;flex-wrap:wrap}
.filter-form{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.filter-form input,.filter-form button,.store-detail-form input,.store-detail-form select,.store-detail-form button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px;box-sizing:border-box}
.filter-form input{min-width:360px}
.filter-form button,.store-detail-form button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
.meta{margin:10px 0 0;color:#46658b;font-size:13px}
.status{margin:10px 0 0;padding:10px 12px;border-radius:10px;background:#edf7ed;border:1px solid #c9e3c9;color:#25603b}
.store-layout{display:grid;grid-template-columns:320px minmax(0,1fr);gap:16px;margin-top:14px;align-items:start}
.store-list-panel,.store-detail-panel{border:1px solid #d3dff0;border-radius:14px;background:#fdfefe;overflow:hidden}
.panel-title{padding:12px 14px;background:#f5f8fd;border-bottom:1px solid #d3dff0;color:#123c73;font-weight:700}
.store-list{max-height:70vh;overflow:auto}
.store-list-item{display:block;padding:12px 14px;border-bottom:1px solid #e5edf8;text-decoration:none;color:#1f2937;background:#fff}
.store-list-item:hover{background:#f8fbff}
.store-list-item-active{background:#e8f1ff}
.store-list-main{display:flex;gap:8px;align-items:center}
.store-list-code{font-weight:700;color:#123c73;min-width:48px}
.store-list-name{font-weight:600}
.store-list-sub{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:6px;font-size:12px;color:#5b708f}
.store-list-badge{display:inline-flex;padding:2px 8px;border-radius:999px;background:#fff1e6;color:#9a4d00;border:1px solid #f3d1b2}
.store-detail-form{padding:14px}
.store-detail-panel.editing .store-view{display:none}
.store-detail-panel:not(.editing) .store-edit{display:none}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.detail-field{display:flex;flex-direction:column;gap:6px}
.detail-field span{font-size:13px;color:#46658b;font-weight:600}
.detail-field-wide{grid-column:1 / -1}
.detail-field input,.detail-field select{width:100%}
.detail-field input[disabled]{background:#f7f9fc;color:#6d7f97}
.detail-field-check{justify-content:flex-end}
.detail-check-wrap{display:flex;align-items:center;min-height:36px}
.detail-check-wrap input[type="checkbox"]{width:18px;height:18px}
.detail-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
.detail-value{min-height:36px;padding:8px 10px;border:1px solid #d3dff0;border-radius:8px;background:#f7f9fc;color:#1f2937;box-sizing:border-box}
.detail-value-empty{color:#8ca0ba}
.btn-secondary{display:inline-flex;padding:6px 10px;border:1px solid #b7ccef;border-radius:8px;background:#f5f8fd;color:#1f4f8f;text-decoration:none;font-weight:700;cursor:pointer}
.store-empty{padding:18px;color:#5b708f}
@media (max-width: 980px){.store-layout{grid-template-columns:1fr}.store-list{max-height:none}.detail-grid{grid-template-columns:1fr}}
</style>
