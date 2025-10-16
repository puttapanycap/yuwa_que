<?php
// เพิ่มฟังก์ชันสำหรับ monitor display
function renderMonitorNotificationSystem($servicePointId = null) {
    ?>
    <div id="monitorNotificationSystem" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
        <!-- Notifications will be inserted here -->
    </div>
    
    <script>
    class MonitorNotificationSystem {
        constructor(servicePointId = null) {
            this.servicePointId = servicePointId;
            this.container = document.getElementById('monitorNotificationSystem');
            this.lastCheck = null;
            this.checkInterval = 3000; // 3 วินาที
            this.notifications = new Map();
            
            this.init();
        }
        
        init() {
            this.loadNotifications();
            setInterval(() => this.loadNotifications(), this.checkInterval);
        }
        
        async loadNotifications() {
            try {
                const url = new URL('../api/get_monitor_notifications.php', window.location.href);
                if (this.servicePointId) {
                    url.searchParams.set('service_point_id', this.servicePointId);
                }
                if (this.lastCheck) {
                    url.searchParams.set('last_check', this.lastCheck);
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    this.processNotifications(data.notifications);
                    this.lastCheck = data.timestamp;
                }
            } catch (error) {
                console.error('Failed to load monitor notifications:', error);
            }
        }
        
        processNotifications(notifications) {
            notifications.forEach(notification => {
                if (!this.notifications.has(notification.notification_id)) {
                    this.showNotification(notification);
                }
            });
        }
        
        showNotification(notification) {
            const element = this.createNotificationElement(notification);
            this.container.appendChild(element);
            this.notifications.set(notification.notification_id, element);
            
            // Auto dismiss
            const dismissTime = notification.display_duration || 5000;
            setTimeout(() => {
                this.dismissNotification(notification.notification_id);
            }, dismissTime);
            
            // Animation
            setTimeout(() => {
                element.classList.add('show');
            }, 100);
        }
        
        createNotificationElement(notification) {
            const div = document.createElement('div');
            div.className = 'monitor-notification';
            div.style.cssText = `
                background: ${notification.bg_color || 'rgba(255,255,255,0.95)'};
                border-left: 4px solid ${notification.color || '#007bff'};
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                backdrop-filter: blur(10px);
                transform: translateX(100%);
                transition: all 0.3s ease;
                opacity: 0;
                color: #333;
                font-family: 'Sarabun', sans-serif;
            `;
            
            div.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <i class="${notification.icon}" style="color: ${notification.color}; font-size: 18px; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        ${notification.title ? `<div style="font-weight: 600; margin-bottom: 5px; color: ${notification.color};">${notification.title}</div>` : ''}
                        <div style="font-size: 14px; line-height: 1.4;">${notification.formatted_message}</div>
                        ${notification.service_point_name ? `<div style="font-size: 12px; color: #666; margin-top: 5px;">📍 ${notification.service_point_name}</div>` : ''}
                    </div>
                    <button onclick="monitorNotificationSystem.dismissNotification(${notification.notification_id})" 
                            style="background: none; border: none; color: #999; cursor: pointer; font-size: 16px; padding: 0; width: 20px; height: 20px;">×</button>
                </div>
            `;
            
            // Add show class for animation
            div.classList.add('monitor-notification');
            
            return div;
        }
        
        dismissNotification(notificationId) {
            const element = this.notifications.get(notificationId);
            if (element) {
                element.style.transform = 'translateX(100%)';
                element.style.opacity = '0';
                
                setTimeout(() => {
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                    this.notifications.delete(notificationId);
                }, 300);
            }
        }
    }
    
    // CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .monitor-notification.show {
            transform: translateX(0) !important;
            opacity: 1 !important;
        }
        
        .monitor-notification:hover {
            transform: translateX(-5px) !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;
        }
    `;
    document.head.appendChild(style);
    
    // Initialize
    window.monitorNotificationSystem = new MonitorNotificationSystem(<?php echo json_encode($servicePointId); ?>);
    </script>
    <?php
}

