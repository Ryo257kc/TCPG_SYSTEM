<script>
    (() => {
        const detailMap = @json($report['detail_map'] ?? []);
        const popover = document.getElementById('laborInsuranceAmountPopover');
        if (!popover) {
            return;
        }

        const titleEl = popover.querySelector('.amount-popover__title');
        const metaEl = popover.querySelector('.amount-popover__meta');
        const bodyEl = popover.querySelector('.amount-popover__body');
        const closeBtn = popover.querySelector('.amount-popover__close');
        let activeTrigger = null;

        const closePopover = () => {
            popover.hidden = true;
            if (activeTrigger) {
                activeTrigger.classList.remove('is-active');
                activeTrigger = null;
            }
        };

        const renderRows = (detail) => {
            if (!detail || !Array.isArray(detail.rows) || detail.rows.length === 0) {
                bodyEl.innerHTML = '<div class="amount-popover__empty">対象データがありません。</div>';
                return;
            }

            bodyEl.innerHTML = detail.rows.map((row) => `
                <div class="amount-popover__row">
                    <span class="amount-popover__name">${row.staff_name ?? ''}</span>
                    <span class="amount-popover__amount">${Number(row.amount ?? 0).toLocaleString('ja-JP')}円</span>
                </div>
            `).join('');
        };

        const openPopover = (trigger) => {
            const key = trigger.dataset.detailKey || '';
            const detail = detailMap[key];
            if (!detail) {
                closePopover();
                return;
            }

            if (activeTrigger && activeTrigger !== trigger) {
                activeTrigger.classList.remove('is-active');
            }

            activeTrigger = trigger;
            trigger.classList.add('is-active');

            titleEl.textContent = detail.label || '対象者一覧';
            metaEl.textContent = `${Number(detail.total_count || 0).toLocaleString('ja-JP')}人 / ${Number(detail.total_amount || 0).toLocaleString('ja-JP')}円`;
            renderRows(detail);

            popover.hidden = false;

            const triggerRect = trigger.getBoundingClientRect();
            const top = window.scrollY + triggerRect.bottom + 8;
            const left = Math.max(16, Math.min(
                window.scrollX + triggerRect.left - 12,
                window.scrollX + document.documentElement.clientWidth - popover.offsetWidth - 16
            ));

            popover.style.top = `${top}px`;
            popover.style.left = `${left}px`;
        };

        document.querySelectorAll('.amount-popover-trigger').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (activeTrigger === trigger && !popover.hidden) {
                    closePopover();
                    return;
                }
                openPopover(trigger);
            });
        });

        closeBtn?.addEventListener('click', closePopover);
        document.addEventListener('click', (event) => {
            if (popover.hidden) {
                return;
            }
            if (popover.contains(event.target)) {
                return;
            }
            if (event.target.closest('.amount-popover-trigger')) {
                return;
            }
            closePopover();
        });
        window.addEventListener('resize', closePopover);
        window.addEventListener('scroll', closePopover, true);
    })();
</script>
