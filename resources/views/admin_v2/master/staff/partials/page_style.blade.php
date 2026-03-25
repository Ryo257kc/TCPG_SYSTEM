<style>
body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px}
.page{max-width:1440px;margin:18px auto}
.card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
.btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
.toolbar{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;align-items:center}
.toolbar h2{margin:0}
.toolbar-links{display:flex;gap:8px;flex-wrap:wrap}
.filter-form{margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.filter-form input,.filter-form button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
.filter-form button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
.meta{margin:10px 0 0}
.staff-layout{display:flex;gap:16px;margin-top:14px;align-items:flex-start}
.staff-list-panel{flex:0 0 300px;max-width:300px;border:1px solid #d3dff0;border-radius:14px;background:#fdfefe;overflow:hidden;min-width:0}
.staff-detail-panel{flex:1 1 auto;border:1px solid #d3dff0;border-radius:14px;background:#fdfefe;overflow:hidden;min-width:0}
.panel-title{padding:12px 14px;background:#f5f8fd;border-bottom:1px solid #d3dff0;color:#123c73;font-weight:700}
.staff-list{max-height:70vh;overflow:auto}
.staff-list-item{display:block;padding:12px 14px;border-bottom:1px solid #e5edf8;text-decoration:none;color:#1f2937;background:#fff}
.staff-list-item:hover{background:#f8fbff}
.staff-list-item-active{background:#e8f1ff}
.staff-list-main{display:flex;gap:8px;align-items:center}
.staff-list-id{font-weight:700;color:#123c73;min-width:48px}
.staff-list-name{font-weight:600}
.staff-list-sub{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:6px;font-size:12px;color:#5b708f}
.staff-tab-bar{display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid #d3dff0;background:#fbfdff}
.staff-tab{display:inline-flex;padding:7px 12px;border:1px solid #c9d7eb;border-radius:999px;background:#fff;color:#46658b;font-weight:700;text-decoration:none}
.staff-tab.is-active{background:#1f4f8f;border-color:#1f4f8f;color:#fff}
.staff-tab-panels{padding:10px}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.detail-field{display:grid;grid-template-columns:88px minmax(0,1fr);gap:3px;align-items:center}
.detail-field-compact{gap:2px}
.detail-field-tight{grid-template-columns:56px minmax(0,1fr);gap:2px}
.detail-field-work{grid-template-columns:118px minmax(0,72px)}
.detail-field span{font-size:12px;color:#46658b;font-weight:700;line-height:1.2}
.detail-value-sub{margin-top:2px;font-size:12px;color:#5b708f;line-height:1.2}
.detail-field-wide{grid-column:1 / -1}
.detail-field-textarea{grid-template-columns:1fr;gap:6px;align-items:stretch}
.detail-field-textarea-no-label{gap:0}
.detail-section{padding:8px 10px;border:1px solid #c8d9f0;border-radius:10px;background:#eaf2ff;margin-top:6px}
.detail-section span{display:block;font-size:13px;color:#123c73;font-weight:800;letter-spacing:.02em}
.detail-value{min-height:24px;padding:2px 0 3px;border:0;border-bottom:1px solid #d3dff0;border-radius:0;background:transparent;color:#1f2937;box-sizing:border-box;line-height:1.25;width:100%;font-size:13px}
.detail-value-bool{display:inline-flex;align-items:center;justify-content:center;min-height:20px;max-width:52px;padding:0 4px;font-size:15px;line-height:1;background:transparent;border:0;border-bottom:1px solid #d3dff0}
.detail-value-bool-tight{min-height:24px;justify-content:flex-start}
.detail-value-bool-rights{display:inline-flex;align-items:center;justify-content:center;min-height:20px;width:20px;padding:0;font-size:15px;line-height:1;border:0;background:transparent;border-radius:0}
.detail-value-textarea{min-height:128px;padding:4px 6px;border:1px solid #d3dff0;border-radius:4px;background:#f7f9fc;white-space:pre-wrap;align-content:flex-start;overflow:auto;line-height:1.1}
.detail-value-empty{color:#8ca0ba}
.staff-display-value{min-height:24px;padding:2px 0 3px;border:0;border-bottom:1px solid #d3dff0;border-radius:0;background:transparent;color:#1f2937;box-sizing:border-box;line-height:1.25;width:100%;font-size:13px}
.staff-info-form .staff-info-edit{display:none !important}
.staff-info-form.editing .staff-info-view{display:none !important}
.staff-info-form.editing .staff-info-edit{display:block !important}
.detail-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
.btn-secondary{display:inline-flex;padding:6px 10px;border:1px solid #b7ccef;border-radius:8px;background:#f5f8fd;color:#1f4f8f;text-decoration:none;font-weight:700;cursor:pointer}
.staff-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.staff-edit-grid .detail-field-wide{grid-column:1 / -1}
.staff-edit-grid input,.staff-edit-grid select,.staff-edit-grid textarea{width:100%;padding:6px 8px;border:1px solid #d3dff0;border-radius:8px;box-sizing:border-box;font:inherit}
.staff-edit-grid textarea{min-height:96px;resize:vertical}
.checkbox-line{display:flex;align-items:center;gap:8px;min-height:36px}
.staff-info-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.info-block{display:flex;flex-direction:column;gap:8px;padding:10px;border:1px solid #dbe6f5;border-radius:10px;background:#fbfdff}
.info-block-wide{grid-column:1 / -1}
.info-block-title{font-size:13px;font-weight:800;color:#123c73;padding-bottom:6px;border-bottom:1px solid #dbe6f5}
.info-block-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 8px}
.name-block-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 8px}
.name-block-wide{grid-column:1 / -1}
.detail-pair{display:flex;flex-direction:column;gap:4px;padding:0;border:0;background:transparent}
.detail-pair-wide{grid-column:1 / -1}
.detail-pair-inline{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 8px}
.detail-pair-inline-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px 8px}
.detail-divider{height:1px;background:#dbe6f5;margin:2px 0 4px}
.detail-divider-wide{grid-column:1 / -1}
.address-block-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.address-block-wide{grid-column:1 / -1}
.contract-block-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.contract-block-wide{grid-column:1 / -1}
.contract-inline-field{align-items:start}
.contract-inline-values{display:flex;gap:8px;align-items:center;min-width:0}
.contract-inline-text{flex:1 1 auto;min-width:0}
.related-card{border:1px solid #d3dff0;border-radius:12px;background:#fff;overflow:hidden}
.related-header{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f8fbff;border-bottom:1px solid #dbe6f5;color:#46658b}
.related-header h3{margin:0;font-size:14px;color:#123c73}
.wrap{overflow:auto}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border:1px solid #d3dff0;padding:6px;white-space:nowrap;text-align:center}
th{background:#f5f8fd}
.staff-empty{padding:18px;color:#5b708f}
@media (max-width: 760px){.staff-layout{display:block}.staff-list-panel{max-width:none;width:100%;margin-bottom:16px}.staff-list{max-height:none}.detail-grid{grid-template-columns:1fr}.staff-info-sections{grid-template-columns:1fr}.info-block-grid{grid-template-columns:1fr}.name-block-grid{grid-template-columns:1fr}.address-block-grid{grid-template-columns:1fr}.contract-block-grid{grid-template-columns:1fr}.contract-inline-values{flex-direction:column;align-items:stretch}.detail-field{grid-template-columns:1fr;gap:3px}}
</style>
