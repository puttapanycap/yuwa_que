<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

// Refresh accessible service points from database on each request
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT sp.service_point_id, sp.point_name, sp.position_key
        FROM staff_service_point_access sspa
        JOIN service_points sp ON sspa.service_point_id = sp.service_point_id
        WHERE sspa.staff_id = ? AND sp.is_active = 1
        ORDER BY sp.display_order, sp.point_name
    ");
    $stmt->execute([$_SESSION['staff_id']]);
    $_SESSION['service_points'] = $stmt->fetchAll();
} catch (Exception $e) {
    // If database refresh fails, keep existing session data
    Logger::error('Failed to refresh service points: ' . $e->getMessage(), [
        'staff_id' => $_SESSION['staff_id'] ?? null
    ]);
}

// Get current user's accessible service points
$accessibleServicePoints = $_SESSION['service_points'] ?? [];
$selectedServicePoint = $_GET['service_point'] ?? ($accessibleServicePoints[0]['service_point_id'] ?? null);

if (!$selectedServicePoint) {
    die('ไม่มีสิทธิ์เข้าถึงจุดบริการใดๆ');
}

// Verify access to selected service point
$hasAccess = false;
foreach ($accessibleServicePoints as $sp) {
    if ($sp['service_point_id'] == $selectedServicePoint) {
        $hasAccess = true;
        $currentServicePointName = $sp['point_name'];
        break;
    }
}

if (!$hasAccess) {
    die('ไม่มีสิทธิ์เข้าถึงจุดบริการนี้');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคิว - <?php echo htmlspecialchars($currentServicePointName); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
            font-size: 16px;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .queue-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .queue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .queue-number {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: bold;
            color: #007bff;
        }
        
        .queue-status {
            font-size: 0.9rem;
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .btn-action {
            border-radius: 12px;
            font-weight: 600;
            margin: 0.3rem;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            min-height: 50px;
            transition: all 0.2s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-action:active {
            transform: translateY(0);
        }
        
        .current-queue {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: 3px solid #0056b3;
        }
        
        .waiting-queue {
            background: white;
            border: 2px solid #e9ecef;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .control-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            position: sticky;
            top: 20px;
        }
        
        .current-status-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .current-status-banner.no-queue {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        
        .current-queue-number {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .current-queue-info {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Tablet Portrait Optimization (9-11 inch) */
        @media (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
            .container-fluid {
                padding: 0 1rem;
            }
            
            .control-panel {
                padding: 1.5rem;
                position: static;
            }
            
            .btn-action {
                font-size: 1.1rem;
                padding: 1rem 1.5rem;
                min-height: 60px;
                margin: 0.4rem 0;
            }
            
            .queue-number {
                font-size: 2.2rem;
            }
            
            .stats-card {
                padding: 1.2rem;
            }
            
            .current-queue-number {
                font-size: 2.5rem;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
        }
        
        /* Desktop Optimization (19 inch) */
        @media (min-width: 1200px) {
            .container-fluid {
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .control-panel {
                padding: 2.5rem;
            }
            
            .btn-action {
                font-size: 1.1rem;
                padding: 1rem 2rem;
                min-height: 55px;
            }
            
            .queue-card {
                margin-bottom: 1.5rem;
            }
            
            .stats-card {
                padding: 2rem;
            }
            
            .current-status-banner {
                padding: 2rem;
            }
        }
        
        /* Mobile Optimization */
        @media (max-width: 767px) {
            .btn-action {
                font-size: 1rem;
                padding: 0.9rem 1.2rem;
                min-height: 55px;
                margin: 0.2rem 0;
            }
            
            .control-panel {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .current-status-banner {
                padding: 1.2rem;
                margin-bottom: 1rem;
            }
            
            .current-queue-number {
                font-size: 2rem;
            }
        }
        
        /* Touch-friendly improvements */
        @media (pointer: coarse) {
            .btn-action {
                min-height: 60px;
                padding: 1rem 1.5rem;
            }
            
            .queue-card {
                padding: 0.5rem;
            }
        }
        
        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .btn-action {
                border-width: 1px;
            }
            
            .queue-card {
                box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            }
        }
        
        /* Animation improvements */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital-alt me-2"></i>
                ระบบเรียกคิว - <?php echo htmlspecialchars($currentServicePointName); ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (count($accessibleServicePoints) > 1): ?>
                            <li><h6 class="dropdown-header">เปลี่ยนจุดบริการ</h6></li>
                            <?php foreach ($accessibleServicePoints as $sp): ?>
                                <li>
                                    <a class="dropdown-item <?php echo ($sp['service_point_id'] == $selectedServicePoint) ? 'active' : ''; ?>" 
                                       href="?service_point=<?php echo $sp['service_point_id']; ?>">
                                        <?php echo htmlspecialchars($sp['point_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <?php if (hasPermission('view_reports')): ?>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>รายงาน</a></li>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_users')): ?>
                            <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="fas fa-cog me-2"></i>จัดการระบบ</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Current Queue Status Banner -->
    <div class="container-fluid mt-3">
        <div id="currentStatusBanner" class="current-status-banner no-queue">
            <div class="current-queue-number">ไม่มีคิว</div>
            <div class="current-queue-info">ไม่มีคิวที่กำลังให้บริการ</div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Control Panel -->
            <div class="col-md-4">
                <div class="control-panel">
                    <h5 class="mb-3">
                        <i class="fas fa-control me-2"></i>ควบคุมคิว
                    </h5>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-lg btn-action" onclick="callNextQueue()" id="callNextBtn">
                            <i class="fas fa-bullhorn me-2"></i>เรียกคิวถัดไป
                        </button>
                        
                        <button class="btn btn-info btn-action" onclick="recallQueue()" id="recallBtn" disabled>
                            <i class="fas fa-redo me-2"></i>เรียกซ้ำ
                        </button>
                        
                        <button class="btn btn-secondary btn-action" onclick="startService()" id="startServiceBtn" disabled>
                            <i class="fas fa-play me-2"></i>เริ่มให้บริการ
                        </button>
                        
                        <button class="btn btn-warning btn-action" onclick="holdQueue()" id="holdBtn" disabled>
                            <i class="fas fa-pause me-2"></i>พักคิว
                        </button>
                        
                        <button class="btn btn-primary btn-action" onclick="completeQueue()" id="completeBtn" disabled>
                            <i class="fas fa-check me-2"></i>เสร็จสิ้น & ส่งต่อ
                        </button>
                        
                        <button class="btn btn-danger btn-action" onclick="cancelQueue()" id="cancelBtn" disabled>
                            <i class="fas fa-times me-2"></i>ยกเลิกคิว
                        </button>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="stats-card">
                            <h3 class="text-primary mb-1" id="waitingCount">0</h3>
                            <small class="text-muted">คิวรอ</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-card">
                            <h3 class="text-success mb-1" id="completedCount">0</h3>
                            <small class="text-muted">เสร็จแล้ว</small>
                        </div>
                    </div>
                </div>

                <!-- Call Time Groups -->
                <div class="stats-card mt-3">
                    <h5 class="mb-3"><i class="fas fa-clock me-2"></i>เรียกคิวตามช่วงเวลา</h5>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody id="callTimeGroups"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Queue List -->
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="fas fa-list me-2"></i>รายการคิว</h5>
                    <button class="btn btn-outline-primary" onclick="refreshQueues()">
                        <i class="fas fa-sync-alt me-2"></i>รีเฟรช
                    </button>
                </div>
                
                <!-- Current Queue -->
                <div id="currentQueue" class="mb-4">
                    <!-- Current queue will be loaded here -->
                </div>
                
                <!-- Waiting Queues -->
                <div id="waitingQueues">
                    <!-- Waiting queues will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Complete Queue Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เสร็จสิ้นและส่งต่อคิว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ส่งต่อไปยังจุดบริการ</label>
                        <select class="form-select" id="nextServicePoint">
                            <option value="">เลือกจุดบริการถัดไป</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ (ถ้ามี)</label>
                        <textarea class="form-control" id="completeNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="confirmComplete()">ยืนยัน</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Queue Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยกเลิกคิว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">เหตุผลในการยกเลิก</label>
                        <select class="form-select" id="cancelReason">
                            <option value="">เลือกเหตุผล</option>
                            <option value="ผู้ป่วยไม่มา">ผู้ป่วยไม่มา</option>
                            <option value="ผู้ป่วยขอยกเลิก">ผู้ป่วยขอยกเลิก</option>
                            <option value="ข้อมูลไม่ถูกต้อง">ข้อมูลไม่ถูกต้อง</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-control" id="cancelNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancel()">ยืนยันการยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        let currentQueueId = null;
        let servicePointId = <?php echo $selectedServicePoint; ?>;
        const csrfToken = '<?php echo generateCSRFToken(); ?>';
        
        $(document).ready(function() {
            loadQueues();
            loadServicePoints();
            loadCallTimeGroups();

            // Auto refresh every 10 seconds
            setInterval(loadQueues, 10000);

            // Poll notifications
            pollNotifications();
            setInterval(pollNotifications, 10000);
        });

        function loadQueues() {
            $.get('../api/get_queues.php', {
                service_point_id: servicePointId,
                _: Date.now()
            }, function(data) {
                displayCurrentQueue(data.current);
                displayWaitingQueues(data.waiting);
                updateStats(data.stats);
                updateButtons(data.current);
                loadCallTimeGroups();
            }).fail(function() {
                console.error('Failed to load queues');
            });
        }
        
        function displayCurrentQueue(queue) {
            const container = $('#currentQueue');
            const banner = $('#currentStatusBanner');
            
            if (queue) {
                currentQueueId = queue.queue_id;
                
                // Update banner
                banner.removeClass('no-queue')
                      .addClass('fade-in');
                banner.find('.current-queue-number').text(queue.queue_number);
                banner.find('.current-queue-info').html(`
                    <i class="fas fa-user-md me-2"></i>${queue.type_name}<br>
                    <small><i class="fas fa-clock me-1"></i>เรียกเมื่อ: ${formatTime(queue.last_called_time)} | เรียกแล้ว ${queue.called_count} ครั้ง</small>
                `);
                
                container.html(`
                    <div class="queue-card current-queue fade-in">
                        <div class="card-body px-4 py-2">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="text-white queue-number pulse">${queue.queue_number}</div>
                                    <small class="text-light">${queue.type_name}</small>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-light mb-1">
                                        <i class="fas fa-bullhorn me-2"></i>กำลังให้บริการ
                                    </h6>
                                    <small class="text-light">
                                        <i class="fas fa-clock me-1"></i>
                                        เรียกเมื่อ: ${formatTime(queue.last_called_time)}
                                    </small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge bg-light text-dark fs-6">
                                        <i class="fas fa-redo me-1"></i>เรียกแล้ว ${queue.called_count} ครั้ง
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                currentQueueId = null;
                
                // Update banner
                banner.addClass('no-queue')
                      .removeClass('fade-in');
                banner.find('.current-queue-number').text('ไม่มีคิว');
                banner.find('.current-queue-info').html(`
                    <i class="fas fa-info-circle me-2"></i>ไม่มีคิวที่กำลังให้บริการ<br>
                    <small>กดปุ่ม "เรียกคิวถัดไป" เพื่อเริ่มให้บริการ</small>
                `);
                
                container.html(`
                    <div class="alert alert-info text-center fade-in">
                        <i class="fas fa-info-circle me-2"></i>
                        ไม่มีคิวที่กำลังให้บริการ
                    </div>
                `);
            }
        }
        
        function displayWaitingQueues(queues) {
            const container = $('#waitingQueues');
            
            if (queues.length === 0) {
                container.html(`
                    <div class="alert alert-secondary text-center">
                        <i class="fas fa-clipboard-list me-2"></i>
                        ไม่มีคิวรอ
                    </div>
                `);
                return;
            }
            
            let html = '';
            queues.forEach(function(queue, index) {
                html += `
                    <div class="queue-card waiting-queue">
                        <div class="card-body px-4 py-2">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="queue-number" style="color: #6c757d;">${queue.queue_number}</div>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-1">${queue.type_name}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        ${formatTime(queue.creation_time)}
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <span class="queue-status bg-warning text-dark">
                                        รอคิวที่ ${index + 1}
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick="callSpecificQueue(${queue.queue_id})">
                                        <i class="fas fa-bullhorn me-1"></i>เรียก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }
        
        function updateStats(stats) {
            $('#waitingCount').text(stats.waiting || 0);
            $('#completedCount').text(stats.completed_today || 0);
        }
        
        function updateButtons(currentQueue) {
            const hasCurrentQueue = currentQueue !== null;
            const status = hasCurrentQueue ? (currentQueue.current_status || '') : '';

            $('#callNextBtn').prop('disabled', hasCurrentQueue);
            $('#recallBtn').prop('disabled', !hasCurrentQueue);
            $('#holdBtn').prop('disabled', !hasCurrentQueue);
            $('#completeBtn').prop('disabled', !hasCurrentQueue);
            $('#cancelBtn').prop('disabled', !hasCurrentQueue);
            $('#startServiceBtn').prop('disabled', !(hasCurrentQueue && status === 'called'));
        }

        function loadCallTimeGroups() {
            $.get('../api/get_call_time_groups.php', {
                service_point_id: servicePointId,
                _: Date.now()
            }, function(response) {
                if (response.success) {
                    displayCallTimeGroups(response.groups);
                }
            });
        }

        function displayCallTimeGroups(groups) {
            const container = $('#callTimeGroups');
            container.empty();
            groups.forEach(function(g) {
                container.append(`<tr><td>${g.start} - ${g.end}</td><td class="text-end">${g.count}</td></tr>`);
            });
        }

        function loadServicePoints() {
            $.get('../api/get_service_points.php', { _: Date.now() }, function(data) {
                const select = $('#nextServicePoint');
                select.empty().append('<option value="">เลือกจุดบริการถัดไป</option>');
                
                data.forEach(function(point) {
                    if (point.service_point_id != servicePointId) {
                        select.append(`<option value="${point.service_point_id}">${point.point_name}</option>`);
                    }
                });
            });
        }
        
        function callNextQueue() {
            $.post('../api/call_queue.php', {
                action: 'call_next',
                service_point_id: servicePointId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    loadQueues();
                    showAlert('เรียกคิวสำเร็จ', 'success');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function callSpecificQueue(queueId) {
            $.post('../api/call_queue.php', {
                action: 'call_specific',
                queue_id: queueId,
                service_point_id: servicePointId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    loadQueues();
                    showAlert('เรียกคิวสำเร็จ', 'success');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function recallQueue() {
            if (!currentQueueId) return;
            
            $.post('../api/call_queue.php', {
                action: 'recall',
                queue_id: currentQueueId,
                service_point_id: servicePointId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    loadQueues();
                    showAlert('เรียกซ้ำสำเร็จ', 'success');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }

        function startService() {
            if (!currentQueueId) return;

            $.post('../api/queue_action.php', {
                action: 'start_processing',
                queue_id: currentQueueId,
                service_point_id: servicePointId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    loadQueues();
                    showAlert('เริ่มให้บริการแล้ว', 'success');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function holdQueue() {
            if (!currentQueueId) return;
            
            $.post('../api/queue_action.php', {
                action: 'hold',
                queue_id: currentQueueId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    loadQueues();
                    showAlert('พักคิวสำเร็จ', 'success');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function completeQueue() {
            if (!currentQueueId) return;
            $('#completeModal').modal('show');
        }
        
        function confirmComplete() {
            const nextServicePointId = $('#nextServicePoint').val();
            const notes = $('#completeNotes').val();
            
            $.post('../api/queue_action.php', {
                action: 'complete',
                queue_id: currentQueueId,
                next_service_point_id: nextServicePointId,
                notes: notes,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    $('#completeModal').modal('hide');
                    loadQueues();
                    showAlert('ส่งต่อคิวสำเร็จ', 'success');
                    
                    // Clear form
                    $('#nextServicePoint').val('');
                    $('#completeNotes').val('');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function cancelQueue() {
            if (!currentQueueId) return;
            $('#cancelModal').modal('show');
        }
        
        function confirmCancel() {
            const reason = $('#cancelReason').val();
            const notes = $('#cancelNotes').val();
            
            if (!reason) {
                showAlert('กรุณาเลือกเหตุผลในการยกเลิก', 'warning');
                return;
            }
            
            $.post('../api/queue_action.php', {
                action: 'cancel',
                queue_id: currentQueueId,
                reason: reason,
                notes: notes,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    $('#cancelModal').modal('hide');
                    loadQueues();
                    showAlert('ยกเลิกคิวสำเร็จ', 'success');
                    
                    // Clear form
                    $('#cancelReason').val('');
                    $('#cancelNotes').val('');
                } else {
                    showAlert('เกิดข้อผิดพลาด: ' + response.message, 'danger');
                }
            }).fail(function() {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
            });
        }
        
        function refreshQueues() {
            loadQueues();
            showAlert('รีเฟรชข้อมูลแล้ว', 'info');
        }

        function pollNotifications() {
            $.get('../api/get_notifications.php', { unread_only: true, limit: 5, _: Date.now() }, function(response) {
                if (response.success && Array.isArray(response.notifications)) {
                    response.notifications.forEach(function(n) {
                        const typeMap = { urgent: 'danger', high: 'warning', normal: 'info', low: 'secondary' };
                        const msg = n.title ? n.title + ': ' + n.message : n.message;
                        showAlert(msg, typeMap[n.priority] || 'info');

                        $.post('../api/notification_action.php', {
                            action: 'mark_read',
                            notification_id: n.notification_id,
                            csrf_token: csrfToken
                        });
                    });
                }
            });
        }

        function formatTime(timeString) {
            if (!timeString) return '-';
            const date = new Date(timeString);
            return date.toLocaleTimeString('th-TH', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto dismiss after 3 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 3000);
        }
    </script>
</body>
</html>
