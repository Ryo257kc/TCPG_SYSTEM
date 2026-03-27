<style>
    :root {
        --ink: #222;
        --line: #646464;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: #eceff3;
        color: var(--ink);
        font-family: "Yu Gothic UI", "Hiragino Sans", sans-serif;
    }
    .print-page {
        width: 297mm;
        min-height: 210mm;
        margin: 0 auto;
        padding: 8mm 6.5mm 8mm;
        background: #fff;
    }
    .sheet-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 3mm;
    }
    .sheet-title h1 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .sheet-title p {
        margin: 2px 0 0;
        font-size: 10.5px;
    }
    .sheet-note {
        font-size: 10px;
        text-align: right;
        white-space: nowrap;
        padding-top: 2px;
    }
    .sheet-top {
        display: grid;
        grid-template-columns: 100mm 28mm 1fr;
        gap: 1.5mm;
        margin-bottom: 2mm;
    }
    .box {
        border: 1px solid var(--line);
        background: #fff;
    }
    .box-title {
        padding: 1.5px 4px;
        border-bottom: 1px solid var(--line);
        font-size: 9.5px;
        font-weight: 700;
        text-align: center;
    }
    .labor-no-grid {
        display: grid;
        grid-template-columns: 15mm 15mm 15mm 1fr 18mm;
        min-height: 16mm;
    }
    .part {
        display: grid;
        grid-template-rows: auto 1fr;
        border-right: 1px solid var(--line);
    }
    .part:last-child { border-right: 0; }
    .part span {
        padding: 1px 3px;
        border-bottom: 1px solid var(--line);
        font-size: 8.5px;
        text-align: center;
    }
    .part strong {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
    }
    .dispatch-box {
        display: grid;
        grid-template-rows: auto 1fr 1fr;
    }
    .dispatch-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        align-items: center;
        border-bottom: 1px solid var(--line);
        padding: 0 4px;
        font-size: 9px;
    }
    .dispatch-title {
        grid-template-columns: 1fr;
        justify-items: center;
        font-weight: 700;
        min-height: 6mm;
    }
    .dispatch-row:last-child { border-bottom: 0; }
    .company-grid {
        display: grid;
        grid-template-columns: 1fr 30mm 39mm;
        grid-template-rows: auto auto;
        min-height: 16mm;
    }
    .field {
        display: grid;
        grid-template-rows: auto 1fr;
        border-right: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
        padding: 1px 4px 2px;
        gap: 1px;
    }
    .field span {
        font-size: 8.5px;
    }
    .field strong {
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
    }
    .field-name { grid-column: 1; grid-row: 1; }
    .field-tel { grid-column: 2; grid-row: 1; }
    .field-work { grid-column: 3; grid-row: 1 / 3; border-right: 0; border-bottom: 0; }
    .field-address { grid-column: 1 / 3; grid-row: 2; border-bottom: 0; }
    .field-post { grid-column: 2; grid-row: 2; border-bottom: 0; }
    .sheet-main { margin-top: 1.5mm; }
    .sheet-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9.2px;
    }
    .sheet-table th,
    .sheet-table td {
        border: 1px solid var(--line);
        padding: 2px 3px;
        vertical-align: middle;
    }
    .sheet-table thead th {
        text-align: center;
        background: #fff;
        font-weight: 700;
    }
    .sheet-table .subhead th {
        font-weight: 400;
        font-size: 8.4px;
    }
    .sheet-table .subhead span {
        display: inline-block;
    }
    .sheet-table .subhead th {
        white-space: nowrap;
    }
    .sheet-table tbody th,
    .sheet-table tfoot th {
        text-align: left;
        white-space: nowrap;
        background: #fff;
    }
    .sheet-table td {
        text-align: right;
    }
    .col-label { width: 19mm; }
    .people,
    .yen {
        display: inline-block;
        width: 50%;
    }
    .people {
        text-align: center;
        border-right: 1px solid #bdbdbd;
    }
    .yen {
        text-align: right;
        padding-left: 4px;
        font-variant-numeric: tabular-nums;
    }
    .sum-cell {
        background: #fafafa;
        font-weight: 700;
    }
    .is-bonus th {
        color: #6c4a12;
    }
    .sheet-footer {
        display: grid;
        grid-template-columns: 1.1fr 0.8fr 0.9fr;
        gap: 3mm;
        margin-top: 4mm;
        align-items: start;
    }
    .footer-col {
        min-width: 0;
    }
    .mini-box,
    .transfer-table {
        border: 1px solid var(--line);
    }
    .mini-title {
        padding: 4px 6px;
        font-size: 9px;
        line-height: 1.45;
    }
    .wide-note {
        margin-bottom: 1.5mm;
    }
    .secondary-note {
        margin-bottom: 3mm;
    }
    .b-note-box {
        margin-bottom: 3mm;
    }
    .b-note-text {
        padding: 4px 6px;
        font-size: 9px;
        line-height: 1.45;
    }
    .formula-box.large,
    .formula-box.small {
        display: grid;
        grid-template-columns: 20mm 18mm 10mm 12mm 8mm;
        border: 1px solid var(--line);
        margin-bottom: 3mm;
        align-items: stretch;
    }
    .formula-box .label,
    .formula-box .formula,
    .formula-box .operator,
    .formula-box .result,
    .formula-box .suffix {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px 6px;
        border-right: 1px solid var(--line);
        font-size: 11px;
        font-weight: 700;
    }
    .formula-box .suffix {
        border-right: 0;
    }
    .formula-box .transfer-label {
        grid-column: 1 / -1;
        border-top: 1px solid var(--line);
        padding: 4px 6px;
        font-size: 9px;
        text-align: right;
    }
    .executive-box {
        border: 1px solid var(--line);
    }
    .executive-title {
        padding: 4px 6px;
        font-size: 8.8px;
        border-bottom: 1px solid var(--line);
    }
    .executive-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9.5px;
    }
    .executive-table th,
    .executive-table td {
        border: 1px solid var(--line);
        padding: 4px 5px;
    }
    .executive-table th {
        background: #fff;
        font-weight: 700;
    }
    .transfer-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9.5px;
    }
    .transfer-table th,
    .transfer-table td {
        border: 1px solid var(--line);
        padding: 5px 6px;
        overflow-wrap: anywhere;
    }
    .transfer-table th {
        width: 27mm;
        background: #fff;
        font-weight: 700;
        white-space: normal;
        text-align: center;
    }
    .transfer-table td:nth-child(2) {
        width: 18mm;
        line-height: 1.25;
        word-break: keep-all;
    }
    .transfer-table td:nth-child(3) {
        width: 20mm;
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-weight: 700;
    }
    .transfer-table td:nth-child(4) {
        width: 10mm;
        text-align: center;
    }
    .transfer-table td:last-child {
        width: 24mm;
        font-size: 8.8px;
        line-height: 1.25;
    }
    @page {
        size: A4 landscape;
        margin: 0;
    }
    @media print {
        body { background: #fff; }
        .print-page { margin: 0; min-height: auto; }
    }
</style>
