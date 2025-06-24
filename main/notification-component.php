<?php
// This component should be included in header files
// It provides a notification dropdown with real-time updates
?>
<div class="notification-dropdown dropdown">
    <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
            0
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
            <span>Notifications</span>
            <button class="btn btn-sm btn-link text-decoration-none" id="markAllRead">
                Mark all read
            </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        <div id="notificationList">
            <li class="dropdown-item text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </li>
        </div>
        <li><hr class="dropdown-divider"></li>
        <li class="dropdown-item text-center">
            <a href="#" class="text-decoration-none" id="viewAllNotifications">View all notifications</a>
        </li>
    </ul>
</div>

<style>
.notification-menu {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-radius: 10px;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.notification-item.unread:hover {
    background-color: #bbdefb;
}

.notification-message {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    color: #333;
}

.notification-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.notification-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.dropdown-header {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

#markAllRead {
    font-size: 0.8rem;
    color: #007bff;
}

#markAllRead:hover {
    color: #0056b3;
}
</style>

<script>
class NotificationManager {
    constructor() {
        this.notificationBadge = document.getElementById('notificationBadge');
        this.notificationList = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllRead');
        this.viewAllBtn = document.getElementById('viewAllNotifications');
        
        this.init();
    }
    
    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.startPolling();
    }
    
    setupEventListeners() {
        this.markAllReadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.markAllAsRead();
        });
        
        this.viewAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.viewAllNotifications();
        });
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('../php/notifications.php?action=get');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationBadge(data.unread_count);
                this.renderNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    updateNotificationBadge(count) {
        if (count > 0) {
            this.notificationBadge.textContent = count > 99 ? '99+' : count;
            this.notificationBadge.style.display = 'block';
            this.notificationBadge.classList.add('notification-badge');
        } else {
            this.notificationBadge.style.display = 'none';
            this.notificationBadge.classList.remove('notification-badge');
        }
    }
    
    renderNotifications(notifications) {
        if (notifications.length === 0) {
            this.notificationList.innerHTML = `
                <li class="dropdown-item text-center text-muted py-3">
                    <i class="fas fa-bell-slash"></i> No notifications
                </li>
            `;
            return;
        }
        
        this.notificationList.innerHTML = notifications.map(notification => `
            <li class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${notification.time_ago}</div>
            </li>
        `).join('');
        
        // Add click handlers for notifications
        this.notificationList.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                this.markAsRead(item.dataset.id);
                if (item.dataset.link) {
                    window.location.href = item.dataset.link;
                }
            });
        });
    }
    
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            const response = await fetch('../php/notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Update the UI
                const item = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Reload notifications to update badge
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('../php/notifications.php?action=mark_all_read');
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                this.notificationList.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Reload notifications to update badge
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    viewAllNotifications() {
        // Redirect to a dedicated notifications page
        window.location.href = 'notifications.php';
    }
    
    startPolling() {
        // Poll for new notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NotificationManager();
});
</script> 