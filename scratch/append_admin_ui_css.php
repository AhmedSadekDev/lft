<?php
$cssFile = __DIR__ . '/../public/assets/css/admin-ui.css';
$css = file_get_contents($cssFile);

$extraStyles = <<<'CSS'

/* ==========================================================================
   DASHBOARD REFERENCE WIDGETS & REFINEMENTS
   ========================================================================== */
.lft-kpi-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.lft-kpi-info {
    display: flex;
    flex-direction: column;
}

.lft-kpi-title {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 6px;
}

.lft-kpi-val {
    font-size: 32px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

.lft-kpi-icon-box {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #ffffff;
    flex-shrink: 0;
}

.lft-kpi-icon-box.purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35);
}

.lft-kpi-icon-box.blue {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
}

.lft-kpi-icon-box.green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

.lft-kpi-icon-box.orange {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}

.border-top-subtle {
    border-top: 1px solid var(--border-light);
}

.lft-trend-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
}

.lft-trend-badge.success {
    background: rgba(16, 185, 129, 0.12);
    color: var(--success);
}

.lft-trend-badge.danger {
    background: rgba(239, 68, 68, 0.12);
    color: var(--danger);
}

.lft-trend-text {
    font-size: 11.5px;
    color: var(--text-secondary);
    font-weight: 500;
}

/* View all link button */
.lft-view-all-btn {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    transition: color 0.2s ease;
}

.lft-view-all-btn:hover {
    color: var(--primary-hover);
    text-decoration: underline;
}

/* Stage Donut Legend */
.lft-stage-legend-list {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.lft-stage-item {
    padding: 3px 0;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-inline-end: 8px;
    flex-shrink: 0;
}

/* Financial Summary Boxes */
.lft-fin-summary-box {
    background-color: var(--surface-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.lft-fin-summary-box:hover {
    transform: translateY(-2px);
    border-color: var(--primary);
}

.lft-fin-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 8px;
}

.lft-fin-icon.green {
    background: rgba(16, 185, 129, 0.12);
    color: var(--success);
}

.lft-fin-icon.red {
    background: rgba(239, 68, 68, 0.12);
    color: var(--danger);
}

.lft-fin-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.lft-fin-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.2;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}

/* Recent Activity Timeline */
.lft-activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.lft-activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.lft-activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.lft-activity-dot.green { background-color: var(--success); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }
.lft-activity-dot.blue { background-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
.lft-activity-dot.orange { background-color: var(--warning); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); }

.lft-activity-content {
    display: flex;
    flex-direction: column;
}

.lft-activity-title {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.3;
}

.lft-activity-time {
    font-size: 11.5px;
    color: var(--text-secondary);
    margin-top: 2px;
}

CSS;

if (strpos($css, 'lft-kpi-icon-box') === false) {
    file_put_contents($cssFile, $css . $extraStyles);
    echo "admin-ui.css updated with extra dashboard styles!\n";
} else {
    echo "admin-ui.css already contains styles.\n";
}
