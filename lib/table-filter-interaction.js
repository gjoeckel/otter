// Enhanced table interaction: toggles both <tbody> and associated Filter widget for each table
// Usage: For each table with a filter widget, ensure the filter widget's id is <table-id>-search-widget

document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('[TFI] init: table-filter-interaction.js loaded');
        // Find all tables with a toggle button
        const tables = document.querySelectorAll('table');
        console.log('[TFI] tables found:', tables.length);
        tables.forEach(table => {
            try {
                const tableId = table.id;
                const hasId = Boolean(tableId);
                const toggleBtn = table.querySelector('.table-toggle-button');
                const tbody = table.querySelector('tbody');
                const filterWidgetId = hasId ? tableId.replace(/-data$/, '') + '-search-widget' : '(no id)';
                const filterWidget = hasId ? document.getElementById(filterWidgetId) : null;

                console.log('[TFI] table check:', {
                    tableId,
                    hasId,
                    hasToggle: Boolean(toggleBtn),
                    hasTbody: Boolean(tbody),
                    filterWidgetId,
                    hasFilterWidget: Boolean(filterWidget)
                });

                if (!toggleBtn || !tbody) return;

                // Set default state
                if (tableId === 'systemwide-data') {
                    // Systemwide: keep rows visible, hide only widget by default
                    if (filterWidget) filterWidget.style.display = 'none';
                } else {
                    // Other tables: collapse rows and widget by default
                    tbody.style.display = 'none';
                    if (filterWidget) filterWidget.style.display = 'none';
                }
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('aria-label', 'Show table filter and data rows');
                console.log('[TFI] default collapsed set for', tableId);

                // Store original scroll position for each table
                let originalScrollPosition = 0;
                
                toggleBtn.addEventListener('click', function () {
                    try {
                        const expandedBefore = toggleBtn.getAttribute('aria-expanded') === 'true';
                        console.log('[TFI] click:', { tableId, expandedBefore });
                        if (expandedBefore) {
                            // Collapse: Systemwide hides only widget, others hide rows and widget
                            if (tableId === 'systemwide-data') {
                                if (filterWidget) filterWidget.style.display = 'none';
                            } else {
                                tbody.style.display = 'none';
                                if (filterWidget) filterWidget.style.display = 'none';
                            }
                            toggleBtn.setAttribute('aria-expanded', 'false');
                            toggleBtn.setAttribute('aria-label', 'Show table filter and data rows');
                            console.log('[TFI] collapsed:', { tableId, tbodyDisplay: tbody.style.display, widgetDisplay: filterWidget ? filterWidget.style.display : '(none)' });
                            // Scroll back
                            window.scrollTo({ top: originalScrollPosition, behavior: 'smooth' });
                        } else {
                            // Store current scroll position before expanding
                            originalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                            // Expand: Systemwide shows only widget; others show rows and widget
                            if (tableId === 'systemwide-data') {
                                if (filterWidget) filterWidget.style.display = '';
                            } else {
                                tbody.style.display = '';
                                if (filterWidget) filterWidget.style.display = '';
                            }
                            toggleBtn.setAttribute('aria-expanded', 'true');
                            toggleBtn.setAttribute('aria-label', 'Hide table filter and data rows');
                            console.log('[TFI] expanded:', { tableId, tbodyDisplay: tbody.style.display, widgetDisplay: filterWidget ? filterWidget.style.display : '(none)' });
                            // Scroll to widget
                            if (filterWidget) {
                                setTimeout(() => {
                                    const header = document.querySelector('header');
                                    const headerHeight = header ? header.offsetHeight : 0;
                                    const targetPosition = filterWidget.offsetTop - headerHeight - 30;
                                    console.log('[TFI] scrollTo:', { tableId, headerHeight, targetPosition });
                                    window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                                }, 100);
                            }
                        }
                    } catch (err) {
                        console.error('[TFI] click handler error for', tableId, err);
                    }
                });
            } catch (err) {
                console.error('[TFI] table loop error', err);
            }
        });
    } catch (e) {
        console.error('[TFI] init error', e);
    }
}); 