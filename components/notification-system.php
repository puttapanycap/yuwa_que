<?php
// Notification System Component
function renderNotificationSystem() {
?>
<div id="notificationSystem" class="notification-system">
    <div class="notification-container" id="notificationContainer">
        <!-- Notifications will be inserted here -->
    </div>
    
    <div class="notification-controls">
        <button class="btn btn-sm btn-outline-secondary" onclick="toggleNotifications()">
            <i class="fas fa-bell" id="notificationIcon"></i>
            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
        </button>
    </div>
</div>

<style>
.notification-system {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.notification-container {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 10px;
}

.notification-item {
    background: white;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-left: 4px solid #007bff;
    animation: slideInRight 0.3s ease-out;
    position: relative;
}

.notification-item.success {
    border-left-color: #28a745;
}

.notification-item.warning {
    border-left-color: #ffc107;
}

.notification-item.error {
    border-left-color: #dc3545;
}

.notification-item.info {
    border-left-color: #17a2b8;
}

.notification-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 4px;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}

.notification-time {
    font-size: 0.75rem;
    color: #6c757d;
}

.notification-message {
    font-size: 0.85rem;
    color: #555;
    line-height: 1.4;
}

.notification-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 2px 6px;
    border-radius: 4px;
}

.notification-close:hover {
    background: #f8f9fa;
    color: #333;
}

.notification-controls {
    text-align: right;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

.notification-item.fade-out {
    animation: fadeOut 0.3s ease-out forwards;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .notification-system {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .notification-item {
        padding: 10px 12px;
    }
}
</style>

<script>
let notificationSystem = {
    notifications: [],
    isVisible: false,
    
    init: function() {
        this.loadNotifications();
        setInterval(() => this.loadNotifications(), 10000); // Check every 10 seconds
    },
    
    loadNotifications: function() {
        fetch('../api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.notifications) {
                    this.updateNotifications(data.notifications);
                }
            })
            .catch(error => {
                console.error('Failed to load notifications:', error);
            });
    },
    
    updateNotifications: function(newNotifications) {
        // Check for new notifications
        const existingIds = this.notifications.map(n => n.queue_id || n.message);
        const reallyNew = newNotifications.filter(n => 
            !existingIds.includes(n.queue_id || n.message)
        );
        
        // Add new notifications
        reallyNew.forEach(notification => {
            this.addNotification(notification);
        });
        
        // Update badge
        this.updateBadge();
    },
    
    addNotification: function(notification) {
        this.notifications.unshift(notification);
        
        // Limit to 20 notifications
        if (this.notifications.length > 20) {
            this.notifications = this.notifications.slice(0, 20);
        }
        
        // Show notification
        this.showNotification(notification);
        
        // Auto-remove after 10 seconds for queue calls
        if (notification.type === 'queue_called') {
            setTimeout(() => {
                this.removeNotification(notification);
            }, 10000);
        }
    },
    
    showNotification: function(notification) {
        const container = document.getElementById('notificationContainer');
        const notificationEl = this.createNotificationElement(notification);
        
        container.insertBefore(notificationEl, container.firstChild);
        
        // Auto-hide after animation
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.classList.add('fade-out');
                setTimeout(() => {
                    if (notificationEl.parentNode) {
                        notificationEl.remove();
                    }
                }, 300);
            }
        }, 5000);
    },
    
    createNotificationElement: function(notification) {
        const div = document.createElement('div');
        div.className = `notification-item ${notification.priority || 'info'}`;
        
        const icon = this.getNotificationIcon(notification.type);
        const time = this.formatTime(notification.timestamp);
        
        div.innerHTML = `
            <div class="notification-header">
                <div class="notification-title">
                    <i class="${icon} me-2"></i>
                    ${this.getNotificationTitle(notification.type)}
                </div>
                <div class="notification-time">${time}</div>
            </div>
            <div class="notification-message">${notification.message}</div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        return div;
    },
    
    getNotificationIcon: function(type) {
        switch (type) {
            case 'queue_called': return 'fas fa-bullhorn text-primary';
            case 'system_warning': return 'fas fa-exclamation-triangle text-warning';
            case 'system_error': return 'fas fa-exclamation-circle text-danger';
            case 'system_info': return 'fas fa-info-circle text-info';
            default: return 'fas fa-bell text-secondary';
        }
    },
    
    getNotificationTitle: function(type) {
        switch (type) {
            case 'queue_called': return 'เรียกคิว';
            case 'system_warning': return 'คำเตือน';
            case 'system_error': return 'ข้อผิดพลาด';
            case 'system_info': return 'ข้อมูล';
            default: return 'แจ้งเตือน';
        }
    },
    
    formatTime: function(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    removeNotification: function(notification) {
        this.notifications = this.notifications.filter(n => 
            (n.queue_id || n.message) !== (notification.queue_id || notification.message)
        );
        this.updateBadge();
    },
    
    updateBadge: function() {
        const badge = document.getElementById('notificationBadge');
        const count = this.notifications.length;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    },
    
    toggle: function() {
        const container = document.getElementById('notificationContainer');
        this.isVisible = !this.isVisible;
        
        if (this.isVisible) {
            container.style.display = 'block';
            this.renderAllNotifications();
        } else {
            container.style.display = 'none';
        }
    },
    
    renderAllNotifications: function() {
        const container = document.getElementById('notificationContainer');
        container.innerHTML = '';
        
        this.notifications.forEach(notification => {
            const notificationEl = this.createNotificationElement(notification);
            container.appendChild(notificationEl);
        });
    }
};

function toggleNotifications() {
    notificationSystem.toggle();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    notificationSystem.init();
});
</script>
<?php
}
?>
