<style>
    :root {
        --bg: #f5f8fd;
        --panel: #ffffff;
        --line: #d7e0eb;
        --text: #253043;
        --muted: #667085;
        --primary: #1f4f8f;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
    }
    .page-shell {
        width: min(1440px, 96vw);
        margin: 18px auto;
        padding: 0;
    }
    .page-head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    .page-title {
        margin: 0;
        font-size: 28px;
        color: var(--primary);
    }
    .page-note {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 13px;
    }
    .page-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        color: var(--text);
        text-decoration: none;
        padding: 9px 14px;
        font-size: 13px;
        cursor: pointer;
    }
    .btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .panel {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(31, 79, 143, 0.05);
    }
    .filter-panel {
        padding: 16px;
        margin-bottom: 10px;
    }
    .filter-form {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: end;
    }
    .field {
        display: grid;
        gap: 6px;
        min-width: 180px;
    }
    .field-year {
        width: 140px;
        min-width: 140px;
    }
    .field-staff {
        flex: 0 1 420px;
        min-width: 320px;
    }
    .field span {
        font-size: 12px;
        color: var(--muted);
        font-weight: 700;
    }
    .field input,
    .field select {
        height: 40px;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 0 12px;
        font-size: 14px;
        background: #fff;
        width: 100%;
    }
    .actions {
        min-width: auto;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 18px;
        align-items: start;
    }
    .summary-panel {
        padding: 16px;
    }
    .summary-head {
        font-size: 13px;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 12px;
    }
    .kv-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }
    .kv-list div {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 10px;
        align-items: baseline;
    }
    .kv-list dt {
        color: var(--muted);
        font-size: 12px;
    }
    .kv-list dd {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
    }
    .history-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 220px;
        gap: 16px;
        align-items: start;
    }
    .schedule-side-panel {
        padding: 14px 12px;
    }
    .grant-schedule {
        display: grid;
        gap: 8px;
    }
    .grant-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 14px;
        color: #2b66b1;
    }
    .grant-row strong {
        color: #2b66b1;
        font-weight: 800;
    }
    .table-panel {
        padding: 14px;
    }
    .history-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
    }
    .history-head-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .history-meta {
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }
    .history-add-panel {
        margin-bottom: 12px;
        padding: 12px;
        background: #f9fbfe;
        border: 1px solid var(--line);
        border-radius: 10px;
    }
    .table-wrap {
        overflow-x: auto;
    }
    .history-table {
        width: 100%;
        min-width: 680px;
        border-collapse: collapse;
    }
    .history-table th,
    .history-table td {
        border: 1px solid var(--line);
        padding: 8px 10px;
        font-size: 13px;
        text-align: left;
        white-space: nowrap;
    }
    .history-table th {
        background: #f4f7fb;
    }
    .history-action-cell {
        width: 92px;
        text-align: center;
    }
    .btn-small {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 64px;
    }
    .btn-danger {
        border-color: #e4bcbc;
        color: #a64242;
        background: #fff5f5;
    }
    .history-edit-row td {
        background: #f9fbfe;
        padding: 12px;
    }
    .history-edit-form {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 10px;
        align-items: end;
    }
    .history-edit-stack {
        display: grid;
        gap: 10px;
    }
    .history-edit-form label {
        display: grid;
        gap: 6px;
    }
    .history-edit-form span {
        font-size: 12px;
        color: var(--muted);
        font-weight: 700;
    }
    .history-edit-form input {
        height: 36px;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 0 10px;
        font-size: 13px;
        width: 100%;
        background: #fff;
    }
    .history-edit-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }
    .history-delete-form {
        display: flex;
        justify-content: flex-end;
    }
    .history-delete-form .btn-danger {
        min-width: 120px;
    }
    .empty-cell {
        color: var(--muted);
        text-align: center;
        padding: 28px 12px;
    }
    .history-pager {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
    }
    .history-page-label {
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }
    .btn.is-disabled {
        pointer-events: none;
        opacity: .45;
    }
    @media (max-width: 1100px) {
        .summary-grid,
        .history-layout {
            grid-template-columns: 1fr;
        }
        .history-edit-form {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
