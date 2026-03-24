<style>
    .santei-filter-panel,
    .santei-table-panel {
        padding: 16px 18px;
    }
    .santei-filter-panel {
        margin-bottom: 14px;
    }
    .santei-filter-form {
        display: flex;
        gap: 12px;
        align-items: end;
        flex-wrap: wrap;
    }
    .field {
        display: grid;
        gap: 6px;
        min-width: 160px;
    }
    .field span {
        font-size: 12px;
        color: var(--muted);
        font-weight: 700;
    }
    .field select {
        height: 40px;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 0 12px;
        font-size: 14px;
        background: #fff;
    }
    .santei-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: end;
        margin-bottom: 14px;
    }
    .santei-head h2 {
        margin: 0;
        font-size: 18px;
        color: var(--primary);
    }
    .santei-head p {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 13px;
    }
    .santei-meta {
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }
    .table-wrap {
        overflow-x: auto;
    }
    .santei-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: collapse;
    }
    .santei-table th,
    .santei-table td {
        border: 1px solid var(--line);
        padding: 7px 8px;
        font-size: 13px;
        white-space: nowrap;
        text-align: left;
        vertical-align: middle;
    }
    .santei-table th {
        background: #f4f7fb;
        color: var(--text);
        text-align: center;
        line-height: 1.35;
    }
    .santei-table .num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .santei-table .sub {
        margin-top: 4px;
        color: var(--muted);
        font-size: 12px;
    }
    .empty-cell {
        text-align: center;
        color: var(--muted);
        padding: 24px 12px;
    }
    @media (max-width: 900px) {
        .santei-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
