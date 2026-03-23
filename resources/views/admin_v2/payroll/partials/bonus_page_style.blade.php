<style>
.bonus-main{display:grid;gap:6px}
.bonus-layout{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(260px,0.75fr);gap:6px;align-items:start}
.bonus-main-col,.bonus-side-col,.bonus-memo-stack,.bonus-meta-stack{display:grid;gap:6px}
.bonus-sections{display:grid;gap:6px;align-items:start}
.bonus-sections-main{grid-template-columns:1.15fr 1fr 0.95fr}
.bonus-sections .card{min-width:0}
.bonus-grid-title{margin:0 0 4px;font-size:15px;color:#173f72}
.bonus-kv{width:100%;border-collapse:collapse;font-size:14px}
.bonus-kv td{border-top:1px solid #dde6f4;padding:4px 6px;line-height:1.15}
.bonus-kv td:last-child{text-align:right;font-variant-numeric:tabular-nums;font-weight:700}
.bonus-kv tr.total-row td{background:#eef5ff;color:#1f4f8f;font-weight:800}
.bonus-meta-card{background:#eef1f5;border-color:#d8dee8}
.bonus-meta-card .bonus-grid-title,.bonus-meta-card td{color:#5f6775}
@media (max-width:980px){.bonus-layout,.bonus-sections-main{grid-template-columns:1fr}}
</style>
