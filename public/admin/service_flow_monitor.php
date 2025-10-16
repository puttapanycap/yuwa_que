<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();
checkPermission('view_reports');

$pageTitle = 'ตรวจสอบ Service Flow';
include '../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-route me-2"></i>ตรวจสอบ Service Flow History
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">หมายเลขคิว</label>
                            <input type="text" class="form-control" id="searchQueue" placeholder="ค้นหาหมายเลขคิว">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">วันที่</label>
                            <input type="date" class="form-control" id="searchDate" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">สถานะ</label>
                            <select class="form-control" id="searchStatus">
                                <option value="">ทั้งหมด</option>
                                <option value="waiting">รอเรียก</option>
                                <option value="called">เรียกแล้ว</option>
                                <option value="processing">กำลังให้บริการ</option>
                                <option value="completed">เสร็จสิ้น</option>
                                <option value="cancelled">ยกเลิก</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary d-block w-100" onclick="searchQueues()">
                                <i class="fas fa-search me-1"></i>ค้นหา
                            </button>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="queuesTable">
                            <thead>
                                <tr>
                                    <th>หมายเลขคิว</th>
                                    <th>ประเภทคิว</th>
                                    <th>สถานะปัจจุบัน</th>
                                    <th>จุดบริการปัจจุบัน</th>
                                    <th>เวลาสร้าง</th>
                                    <th>Service Flow</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody id="queuesTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Flow Modal -->
<div class="modal fade" id="serviceFlowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Service Flow History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="serviceFlowContent">
                    <!-- Service flow timeline will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    searchQueues();
});

function searchQueues() {
    const searchQueue = $('#searchQueue').val();
    const searchDate = $('#searchDate').val();
    const searchStatus = $('#searchStatus').val();
    
    $.get('api/get_queues.php', {
        search_queue: searchQueue,
        search_date: searchDate,
        search_status: searchStatus,
        include_flow: 1
    }, function(data) {
        const tbody = $('#queuesTableBody');
        tbody.empty();
        
        if (data.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center">ไม่พบข้อมูล</td></tr>');
            return;
        }
        
        data.forEach(function(queue) {
            const statusBadge = getStatusBadge(queue.current_status);
            const hasFlow = queue.flow_count > 0;
            
            const row = `
                <tr>
                    <td><strong>${queue.queue_number}</strong></td>
                    <td>${queue.type_name}</td>
                    <td>${statusBadge}</td>
                    <td>${queue.service_point_name || '-'}</td>
                    <td>${formatDateTime(queue.creation_time)}</td>
                    <td>
                        ${hasFlow ? 
                            `<span class="badge bg-success">${queue.flow_count} รายการ</span>` : 
                            `<span class="badge bg-danger">ไม่มีข้อมูล</span>`
                        }
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewServiceFlow(${queue.queue_id}, '${queue.queue_number}')">
                            <i class="fas fa-eye me-1"></i>ดู Flow
                        </button>
                        ${!hasFlow ? 
                            `<button class="btn btn-sm btn-outline-warning ms-1" onclick="fixServiceFlow(${queue.queue_id})">
                                <i class="fas fa-wrench me-1"></i>แก้ไข
                            </button>` : ''
                        }
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }).fail(function() {
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    });
}

function viewServiceFlow(queueId, queueNumber) {
    $('#serviceFlowModal .modal-title').text(`Service Flow History - คิว ${queueNumber}`);
    
    $.get('api/get_queue_flow_history.php', { queue_id: queueId }, function(response) {
        if (response.success) {
            const content = $('#serviceFlowContent');
            content.empty();
            
            if (response.history.length === 0) {
                content.html('<div class="alert alert-warning">ไม่พบข้อมูล Service Flow History</div>');
            } else {
                let timeline = '<div class="timeline">';
                
                response.history.forEach(function(item, index) {
                    const isLast = index === response.history.length - 1;
                    const actionIcon = getActionIcon(item.action);
                    const actionColor = getActionColor(item.action);
                    
                    timeline += `
                        <div class="timeline-item">
                            <div class="timeline-marker bg-${actionColor}">
                                <i class="fas ${actionIcon}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">${getActionText(item.action)}</h6>
                                <p class="mb-1">
                                    ${item.from_service_point_name ? `จาก: ${item.from_service_point_name}` : ''}
                                    ${item.to_service_point_name ? `ไป: ${item.to_service_point_name}` : ''}
                                </p>
                                <small class="text-muted">
                                    ${formatDateTime(item.timestamp)}
                                    ${item.staff_name ? ` โดย ${item.staff_name}` : ' (ระบบอัตโนมัติ)'}
                                </small>
                                ${item.notes ? `<p class="mt-1 mb-0"><small>${item.notes}</small></p>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                timeline += '</div>';
                content.html(timeline);
            }
            
            $('#serviceFlowModal').modal('show');
        } else {
            alert('เกิดข้อผิดพลาด: ' + response.message);
        }
    }).fail(function() {
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล Service Flow');
    });
}

function fixServiceFlow(queueId) {
    if (confirm('ต้องการแก้ไข Service Flow สำหรับคิวนี้หรือไม่?')) {
        $.post('api/fix_service_flow.php', { queue_id: queueId }, function(response) {
            if (response.success) {
                alert('แก้ไข Service Flow สำเร็จ');
                searchQueues();
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }).fail(function() {
            alert('เกิดข้อผิดพลาดในการแก้ไข Service Flow');
        });
    }
}

function getStatusBadge(status) {
    const badges = {
        'waiting': '<span class="badge bg-warning">รอเรียก</span>',
        'called': '<span class="badge bg-info">เรียกแล้ว</span>',
        'processing': '<span class="badge bg-primary">กำลังให้บริการ</span>',
        'completed': '<span class="badge bg-success">เสร็จสิ้น</span>',
        'cancelled': '<span class="badge bg-danger">ยกเลิก</span>',
        'forwarded': '<span class="badge bg-secondary">ส่งต่อ</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function getActionIcon(action) {
    const icons = {
        'created': 'fa-plus',
        'called': 'fa-bullhorn',
        'forwarded': 'fa-arrow-right',
        'completed': 'fa-check',
        'cancelled': 'fa-times',
        'recalled': 'fa-redo',
        'skipped': 'fa-forward',
        'hold': 'fa-pause'
    };
    return icons[action] || 'fa-circle';
}

function getActionColor(action) {
    const colors = {
        'created': 'success',
        'called': 'info',
        'forwarded': 'primary',
        'completed': 'success',
        'cancelled': 'danger',
        'recalled': 'warning',
        'skipped': 'secondary',
        'hold': 'warning'
    };
    return colors[action] || 'secondary';
}

function getActionText(action) {
    const texts = {
        'created': 'สร้างคิว',
        'called': 'เรียกคิว',
        'forwarded': 'ส่งต่อคิว',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก',
        'recalled': 'เรียกซ้ำ',
        'skipped': 'ข้าม',
        'hold': 'พักคิว'
    };
    return texts[action] || action;
}

function formatDateTime(datetime) {
    return new Date(datetime).toLocaleString('th-TH');
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -19px;
    top: 30px;
    height: calc(100% + 10px);
    width: 2px;
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}
</style>

<?php include '../components/footer.php'; ?>
