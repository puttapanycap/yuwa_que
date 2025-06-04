<?php
require_once '../config/config.php';
requireLogin();

// Get user preferences
$userPrefs = [];
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM dashboard_user_preferences WHERE staff_id = ?");
    $stmt->execute([$_SESSION['staff_id']]);
    $userPrefs = $stmt->fetch() ?: [];
} catch (Exception $e) {
    Logger::error("Failed to load user preferences: " . $e->getMessage());
}

$refreshInterval = $userPrefs['refresh_interval'] ?? 30;
$theme = $userPrefs['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="th" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analytics - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@9.2.0/dist/gridstack.min.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        [data-theme="dark"] {
            --bs-body-bg: #1a1a1a;
            --bs-body-color: #ffffff;
            --bs-card-bg: #2d2d2d;
            --bs-border-color: #404040;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--bs-body-bg, #f8f9fa);
            color: var(--bs-body-color, #212529);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .widget-card {
            background: var(--bs-card-bg, white);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--bs-border-color, #dee2e6);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .widget-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .counter-widget {
            text-align: center;
            padding: 2rem;
        }
        
        .counter-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .counter-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .chart-widget {
            padding: 1.5rem;
        }
        
        .table-widget {
            padding: 0;
        }
        
        .table-widget .table {
            margin: 0;
        }
        
        .gauge-widget {
            text-align: center;
            padding: 2rem;
        }
        
        .gauge-container {
            position: relative;
            width: 200px;
            height: 100px;
            margin: 0 auto;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-active { background-color: var(--success-color); }
        .status-busy { background-color: var(--warning-color); }
        .status-inactive { background-color: var(--danger-color); }
        
        .alert-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 350px;
            z-index: 1050;
        }
        
        .control-panel {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1040;
        }
        
        .refresh-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1060;
            display: none;
        }
        
        .grid-stack {
            background: transparent;
        }
        
        .grid-stack-item-content {
            background: transparent;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            z-index: 10;
        }
        
        [data-theme="dark"] .loading-overlay {
            background: rgba(45,45,45,0.8);
        }
        
        .widget-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--bs-border-color, #dee2e6);
            font-weight: 600;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .widget-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-widget {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .control-panel,
            .alert-panel {
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .counter-number {
                font-size: 2rem;
            }
            
            .counter-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Analytics</h2>
                    <p class="mb-0">ข้อมูลแบบ Real-time - อัพเดทล่าสุด: <span id="lastUpdate">-</span></p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-light btn-sm" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                        <button class="btn btn-outline-light btn-sm" id="settingsBtn">
                            <i class="fas fa-cog"></i> ตั้งค่า
                        </button>
                        <button class="btn btn-outline-light btn-sm" id="fullscreenBtn">
                            <i class="fas fa-expand"></i> เต็มจอ
                        </button>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="control-panel">
        <div class="card">
            <div class="card-body p-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="editModeBtn">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <select class="form-select form-select-sm" id="refreshInterval" style="width: auto;">
                        <option value="10">10 วินาที</option>
                        <option value="30" <?php echo $refreshInterval == 30 ? 'selected' : ''; ?>>30 วินาที</option>
                        <option value="60">1 นาที</option>
                        <option value="300">5 นาที</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Panel -->
    <div class="alert-panel" id="alertPanel"></div>

    <!-- Refresh Indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="container-fluid">
        <div class="grid-stack" id="dashboardGrid"></div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตั้งค่า Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Widgets ที่ใช้งาน</h6>
                            <div id="availableWidgets"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>การตั้งค่าทั่วไป</h6>
                            <div class="mb-3">
                                <label class="form-label">ความถี่ในการรีเฟรช</label>
                                <select class="form-select" id="modalRefreshInterval">
                                    <option value="10">10 วินาที</option>
                                    <option value="30">30 วินาที</option>
                                    <option value="60">1 นาที</option>
                                    <option value="300">5 นาที</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ธีม</label>
                                <select class="form-select" id="modalTheme">
                                    <option value="light">สว่าง</option>
                                    <option value="dark">มืด</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveSettings">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@9.2.0/dist/gridstack-all.js"></script>
    
    <script>
        class DashboardManager {
            constructor() {
                this.grid = null;
                this.widgets = new Map();
                this.charts = new Map();
                this.refreshInterval = <?php echo $refreshInterval; ?>;
                this.refreshTimer = null;
                this.editMode = false;
                this.theme = '<?php echo $theme; ?>';
                
                this.init();
            }
            
            init() {
                this.initGrid();
                this.loadWidgets();
                this.bindEvents();
                this.startAutoRefresh();
                this.loadAlerts();
            }
            
            initGrid() {
                this.grid = GridStack.init({
                    cellHeight: 80,
                    verticalMargin: 20,
                    horizontalMargin: 20,
                    animate: true,
                    float: false,
                    disableResize: !this.editMode,
                    disableDrag: !this.editMode
                });
            }
            
            async loadWidgets() {
                try {
                    const response = await fetch('../api/get_dashboard_widgets.php');
                    const widgets = await response.json();
                    
                    for (const widget of widgets) {
                        await this.createWidget(widget);
                    }
                    
                    this.updateLastRefresh();
                } catch (error) {
                    console.error('Failed to load widgets:', error);
                    this.showAlert('error', 'เกิดข้อผิดพลาดในการโหลด widgets');
                }
            }
            
            async createWidget(config) {
                const widgetId = `widget-${config.widget_id}`;
                const gridItem = this.grid.addWidget({
                    w: config.width || 3,
                    h: config.height || 3,
                    content: this.generateWidgetHTML(config)
                });
                
                gridItem.setAttribute('data-widget-id', config.widget_id);
                gridItem.setAttribute('data-widget-type', config.widget_type);
                
                this.widgets.set(config.widget_id, {
                    element: gridItem,
                    config: config,
                    lastUpdate: null
                });
                
                // Load widget data
                await this.updateWidget(config.widget_id);
            }
            
            generateWidgetHTML(config) {
                const widgetConfig = JSON.parse(config.widget_config);
                let html = `
                    <div class="widget-card h-100">
                        <div class="widget-header">
                            <span>${config.widget_name}</span>
                            <div class="widget-controls">
                                <button class="btn btn-sm btn-outline-secondary btn-widget" onclick="dashboard.refreshWidget(${config.widget_id})">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="widget-content" id="widget-content-${config.widget_id}">
                `;
                
                switch (config.widget_type) {
                    case 'counter':
                        html += `
                            <div class="counter-widget">
                                <div class="counter-icon text-${widgetConfig.color}">
                                    <i class="${widgetConfig.icon}"></i>
                                </div>
                                <div class="counter-number text-${widgetConfig.color}" id="counter-${config.widget_id}">-</div>
                                <div class="counter-label">${config.widget_name}</div>
                            </div>
                        `;
                        break;
                        
                    case 'chart':
                        html += `
                            <div class="chart-widget">
                                <canvas id="chart-${config.widget_id}" height="${widgetConfig.height || 200}"></canvas>
                            </div>
                        `;
                        break;
                        
                    case 'table':
                        html += `
                            <div class="table-widget">
                                <div class="table-responsive" style="max-height: ${widgetConfig.height || 300}px;">
                                    <table class="table table-sm" id="table-${config.widget_id}">
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                        break;
                        
                    case 'gauge':
                        html += `
                            <div class="gauge-widget">
                                <div class="gauge-container">
                                    <canvas id="gauge-${config.widget_id}" width="200" height="100"></canvas>
                                </div>
                                <div class="gauge-value" id="gauge-value-${config.widget_id}">-</div>
                            </div>
                        `;
                        break;
                }
                
                html += `
                        </div>
                        <div class="loading-overlay" id="loading-${config.widget_id}" style="display: none;">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                `;
                
                return html;
            }
            
            async updateWidget(widgetId) {
                const widget = this.widgets.get(widgetId);
                if (!widget) return;
                
                const loadingEl = document.getElementById(`loading-${widgetId}`);
                if (loadingEl) loadingEl.style.display = 'flex';
                
                try {
                    const response = await fetch(`../api/get_widget_data.php?widget_id=${widgetId}`);
                    const data = await response.json();
                    
                    this.renderWidgetData(widgetId, data);
                    widget.lastUpdate = new Date();
                    
                } catch (error) {
                    console.error(`Failed to update widget ${widgetId}:`, error);
                } finally {
                    if (loadingEl) loadingEl.style.display = 'none';
                }
            }
            
            renderWidgetData(widgetId, data) {
                const widget = this.widgets.get(widgetId);
                if (!widget) return;
                
                const config = JSON.parse(widget.config.widget_config);
                
                switch (widget.config.widget_type) {
                    case 'counter':
                        this.renderCounter(widgetId, data, config);
                        break;
                    case 'chart':
                        this.renderChart(widgetId, data, config);
                        break;
                    case 'table':
                        this.renderTable(widgetId, data, config);
                        break;
                    case 'gauge':
                        this.renderGauge(widgetId, data, config);
                        break;
                }
            }
            
            renderCounter(widgetId, data, config) {
                const counterEl = document.getElementById(`counter-${widgetId}`);
                if (counterEl && data.value !== undefined) {
                    this.animateNumber(counterEl, parseInt(counterEl.textContent) || 0, data.value);
                }
            }
            
            renderChart(widgetId, data, config) {
                const canvas = document.getElementById(`chart-${widgetId}`);
                if (!canvas) return;
                
                // Destroy existing chart
                if (this.charts.has(widgetId)) {
                    this.charts.get(widgetId).destroy();
                }
                
                const ctx = canvas.getContext('2d');
                const chart = new Chart(ctx, {
                    type: config.type || 'line',
                    data: data.chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: config.type !== 'doughnut' ? {
                            y: {
                                beginAtZero: true
                            }
                        } : {}
                    }
                });
                
                this.charts.set(widgetId, chart);
            }
            
            renderTable(widgetId, data, config) {
                const tableBody = document.querySelector(`#table-${widgetId} tbody`);
                if (!tableBody || !data.rows) return;
                
                let html = '';
                data.rows.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(cell => {
                        html += `<td>${cell}</td>`;
                    });
                    html += '</tr>';
                });
                
                tableBody.innerHTML = html;
            }
            
            renderGauge(widgetId, data, config) {
                const canvas = document.getElementById(`gauge-${widgetId}`);
                const valueEl = document.getElementById(`gauge-value-${widgetId}`);
                
                if (!canvas || data.value === undefined) return;
                
                const ctx = canvas.getContext('2d');
                const value = data.value;
                const max = config.max || 100;
                const percentage = Math.min(value / max, 1);
                
                // Clear canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw gauge
                const centerX = canvas.width / 2;
                const centerY = canvas.height - 10;
                const radius = 80;
                
                // Background arc
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI, 0);
                ctx.strokeStyle = '#e9ecef';
                ctx.lineWidth = 10;
                ctx.stroke();
                
                // Value arc
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI, Math.PI + (Math.PI * percentage));
                ctx.strokeStyle = this.getGaugeColor(percentage);
                ctx.lineWidth = 10;
                ctx.stroke();
                
                // Update value display
                if (valueEl) {
                    valueEl.textContent = `${value}${data.unit || ''}`;
                }
            }
            
            getGaugeColor(percentage) {
                if (percentage < 0.5) return '#28a745';
                if (percentage < 0.8) return '#ffc107';
                return '#dc3545';
            }
            
            animateNumber(element, from, to) {
                const duration = 1000;
                const start = Date.now();
                const difference = to - from;
                
                const animate = () => {
                    const elapsed = Date.now() - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const current = Math.round(from + (difference * progress));
                    
                    element.textContent = current.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };
                
                animate();
            }
            
            async refreshWidget(widgetId) {
                await this.updateWidget(widgetId);
            }
            
            async refreshAll() {
                document.getElementById('refreshIndicator').style.display = 'flex';
                
                const promises = Array.from(this.widgets.keys()).map(id => this.updateWidget(id));
                await Promise.all(promises);
                
                this.updateLastRefresh();
                document.getElementById('refreshIndicator').style.display = 'none';
                
                this.showAlert('success', 'อัพเดทข้อมูลเรียบร้อย', 2000);
            }
            
            startAutoRefresh() {
                this.stopAutoRefresh();
                this.refreshTimer = setInterval(() => {
                    this.refreshAll();
                }, this.refreshInterval * 1000);
            }
            
            stopAutoRefresh() {
                if (this.refreshTimer) {
                    clearInterval(this.refreshTimer);
                    this.refreshTimer = null;
                }
            }
            
            updateLastRefresh() {
                document.getElementById('lastUpdate').textContent = new Date().toLocaleString('th-TH');
            }
            
            toggleEditMode() {
                this.editMode = !this.editMode;
                this.grid.enableMove(this.editMode);
                this.grid.enableResize(this.editMode);
                
                const btn = document.getElementById('editModeBtn');
                if (this.editMode) {
                    btn.innerHTML = '<i class="fas fa-save"></i> บันทึก';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.innerHTML = '<i class="fas fa-edit"></i> แก้ไข';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                    this.saveLayout();
                }
            }
            
            async saveLayout() {
                const layout = this.grid.save();
                try {
                    await fetch('../api/save_dashboard_layout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ layout })
                    });
                    this.showAlert('success', 'บันทึกการจัดเรียงเรียบร้อย');
                } catch (error) {
                    this.showAlert('error', 'เกิดข้อผิดพลาดในการบันทึก');
                }
            }
            
            toggleTheme() {
                this.theme = this.theme === 'light' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', this.theme);
                
                const icon = document.querySelector('#themeToggle i');
                icon.className = this.theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
                
                this.savePreferences();
            }
            
            async savePreferences() {
                try {
                    await fetch('../api/save_dashboard_preferences.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            refresh_interval: this.refreshInterval,
                            theme: this.theme
                        })
                    });
                } catch (error) {
                    console.error('Failed to save preferences:', error);
                }
            }
            
            async loadAlerts() {
                try {
                    const response = await fetch('../api/get_dashboard_alerts.php');
                    const alerts = await response.json();
                    
                    alerts.forEach(alert => {
                        this.showAlert(alert.alert_type, alert.alert_message, 0, alert.alert_id);
                    });
                } catch (error) {
                    console.error('Failed to load alerts:', error);
                }
            }
            
            showAlert(type, message, duration = 5000, alertId = null) {
                const alertPanel = document.getElementById('alertPanel');
                const alertEl = document.createElement('div');
                alertEl.className = `alert alert-${type} alert-dismissible fade show`;
                alertEl.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                if (alertId) {
                    alertEl.setAttribute('data-alert-id', alertId);
                }
                
                alertPanel.appendChild(alertEl);
                
                if (duration > 0) {
                    setTimeout(() => {
                        if (alertEl.parentNode) {
                            alertEl.remove();
                        }
                    }, duration);
                }
            }
            
            bindEvents() {
                document.getElementById('refreshBtn').addEventListener('click', () => this.refreshAll());
                document.getElementById('editModeBtn').addEventListener('click', () => this.toggleEditMode());
                document.getElementById('themeToggle').addEventListener('click', () => this.toggleTheme());
                document.getElementById('fullscreenBtn').addEventListener('click', () => this.toggleFullscreen());
                
                document.getElementById('refreshInterval').addEventListener('change', (e) => {
                    this.refreshInterval = parseInt(e.target.value);
                    this.startAutoRefresh();
                    this.savePreferences();
                });
                
                // Settings modal events
                document.getElementById('settingsBtn').addEventListener('click', () => {
                    const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
                    modal.show();
                });
                
                document.getElementById('saveSettings').addEventListener('click', () => {
                    this.saveSettings();
                });
            }
            
            toggleFullscreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                    document.getElementById('fullscreenBtn').innerHTML = '<i class="fas fa-compress"></i> ออกจากเต็มจอ';
                } else {
                    document.exitFullscreen();
                    document.getElementById('fullscreenBtn').innerHTML = '<i class="fas fa-expand"></i> เต็มจอ';
                }
            }
        }
        
        // Initialize dashboard
        const dashboard = new DashboardManager();
        
        // Handle fullscreen change
        document.addEventListener('fullscreenchange', () => {
            const btn = document.getElementById('fullscreenBtn');
            if (document.fullscreenElement) {
                btn.innerHTML = '<i class="fas fa-compress"></i> ออกจากเต็มจอ';
            } else {
                btn.innerHTML = '<i class="fas fa-expand"></i> เต็มจอ';
            }
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                dashboard.stopAutoRefresh();
            } else {
                dashboard.startAutoRefresh();
                dashboard.refreshAll();
            }
        });
    </script>
</body>
</html>
