:root {
    --surface-1: #ffffff;
    --surface-2: #f8fafc;
    --surface-3: #f1f5f9;
    --surface-4: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-tertiary: #94a3b8;
    --border-color: #e2e8f0;
    --primary: #3b82f6;
    --primary-dark: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
}

[data-theme="dark"] {
    --surface-1: #0f172a;
    --surface-2: #1e293b;
    --surface-3: #334155;
    --surface-4: #475569;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --text-tertiary: #94a3b8;
    --border-color: #334155;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--surface-1);
    color: var(--text-primary);
    line-height: 1.5;
    transition: background-color 0.3s ease;
}

.dashboard-container {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1rem;
}

/* Header Styles */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.header-title h1 {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary), var(--info));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-title p {
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn {
    padding: 0.625rem 1.25rem;
    border-radius: var(--radius-md);
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--surface-2);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--surface-3);
    transform: translateY(-1px);
}

.btn-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-md);
    background: var(--surface-2);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: var(--surface-3);
    color: var(--text-primary);
    transform: translateY(-1px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface-2);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary);
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: var(--radius-md);
    background: rgba(59, 130, 246, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: var(--primary);
    font-size: 1.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.trend-up {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.trend-down {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

/* Charts Section */
.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-container {
    background: var(--surface-2);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.chart-wrapper {
    height: 300px;
    position: relative;
}

/* Performance Metrics */
.metrics-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.top-performers {
    background: var(--surface-2);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.metrics-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.metric-card {
    background: var(--surface-2);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.performer-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--surface-3);
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
    transition: transform 0.2s ease;
}

.performer-card:hover {
    transform: translateX(4px);
    background: var(--surface-4);
}

.performer-rank {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--primary);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.125rem;
}

.performer-info {
    flex: 1;
}

.performer-name {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.performer-score {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.score-value {
    font-weight: 600;
    color: var(--text-primary);
}

.score-grade {
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.grade-excellent { background: rgba(16, 185, 129, 0.1); color: var(--success); }
.grade-great { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.grade-good { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
.grade-average { background: rgba(249, 115, 22, 0.1); color: #f97316; }
.grade-poor { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
.grade-failing { background: rgba(220, 38, 38, 0.1); color: #dc2626; }

/* Table Styles */
.table-container {
    background: var(--surface-2);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: var(--surface-3);
}

th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border-color);
}

td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background: var(--surface-3);
}

.airline-name {
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.airline-code {
    background: var(--surface-4);
    color: var(--text-primary);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.rating-stars {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rating-stars i {
    color: #fbbf24;
}

.rating-value {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.performance-grade {
    display: inline-block;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex;
}

.spinner {
    width: 4rem;
    height: 4rem;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    border-top-color: var(--primary);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Notifications */
.notifications {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1000;
}

.notification {
    background: var(--surface-2);
    border-radius: var(--radius-md);
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid var(--primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: var(--shadow-lg);
    animation: slideIn 0.3s ease;
    max-width: 400px;
}

.notification.error {
    border-left-color: var(--danger);
}

.notification.warning {
    border-left-color: var(--warning);
}

.notification i {
    font-size: 1.25rem;
}

.notification.error i {
    color: var(--danger);
}

.notification.warning i {
    color: var(--warning);
}

.notification button {
    margin-left: auto;
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 1.25rem;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
}

.notification button:hover {
    background: var(--surface-3);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 600px;
    }
}
