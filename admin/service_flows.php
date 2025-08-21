<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_service_points')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'save_flow':
                $queueTypeId = $_POST['queue_type_id'];
                $flows = $_POST['flows'] ?? [];
                
                if (empty($queueTypeId)) {
                    throw new Exception('กรุณาเลือกประเภทคิว');
                }
                
                $db->beginTransaction();
                
                // Delete existing flows for this queue type
                $stmt = $db->prepare("DELETE FROM service_flows WHERE queue_type_id = ?");
                $stmt->execute([$queueTypeId]);
                
                // Insert new flows
                if (!empty($flows)) {
                    $stmt = $db->prepare("
                        INSERT INTO service_flows 
                        (queue_type_id, from_service_point_id, to_service_point_id, sequence_order, is_optional) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($flows as $flow) {
                        $fromId = $flow['from_service_point_id'] ?: null;
                        $toId = $flow['to_service_point_id'];
                        $sequenceOrder = $flow['sequence_order'];
                        $isOptional = isset($flow['is_optional']) ? 1 : 0;
                        
                        $stmt->execute([$queueTypeId, $fromId, $toId, $sequenceOrder, $isOptional]);
                    }
                }
                
                $db->commit();
                
                // Get queue type name for logging
                $stmt = $db->prepare("SELECT type_name FROM queue_types WHERE queue_type_id = ?");
                $stmt->execute([$queueTypeId]);
                $queueType = $stmt->fetch();
                
                logActivity("บันทึก Service Flow สำหรับ {$queueType['type_name']}");
                
                $message = 'บันทึก Service Flow สำเร็จ';
                $messageType = 'success';
                break;
                
            case 'delete_flow':
                $flowId = $_POST['flow_id'];
                
                $stmt = $db->prepare("DELETE FROM service_flows WHERE flow_id = ?");
                $stmt->execute([$flowId]);
                
                logActivity("ลบ Service Flow ID: {$flowId}");
                
                $message = 'ลบ Service Flow สำเร็จ';
                $messageType = 'success';
                break;
                
            case 'copy_flow':
                $fromQueueTypeId = $_POST['from_queue_type_id'];
                $toQueueTypeId = $_POST['to_queue_type_id'];
                
                if ($fromQueueTypeId == $toQueueTypeId) {
                    throw new Exception('ไม่สามารถคัดลอกไปยังประเภทคิวเดียวกันได้');
                }
                
                $db->beginTransaction();
                
                // Delete existing flows for target queue type
                $stmt = $db->prepare("DELETE FROM service_flows WHERE queue_type_id = ?");
                $stmt->execute([$toQueueTypeId]);
                
                // Copy flows
                $stmt = $db->prepare("
                    INSERT INTO service_flows 
                    (queue_type_id, from_service_point_id, to_service_point_id, sequence_order, is_optional)
                    SELECT ?, from_service_point_id, to_service_point_id, sequence_order, is_optional
                    FROM service_flows 
                    WHERE queue_type_id = ?
                ");
                $stmt->execute([$toQueueTypeId, $fromQueueTypeId]);
                
                $db->commit();
                
                logActivity("คัดลอก Service Flow จาก Queue Type ID {$fromQueueTypeId} ไป {$toQueueTypeId}");
                
                $message = 'คัดลอก Service Flow สำเร็จ';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get queue types
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM queue_types WHERE is_active = 1 ORDER BY type_name");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
    // Get service points
    $stmt = $db->prepare("SELECT * FROM service_points WHERE is_active = 1 ORDER BY display_order, point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
} catch (Exception $e) {
    $queueTypes = [];
    $servicePoints = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Service Flow - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .flow-step {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s;
        }
        
        .flow-step:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.1);
        }
        
        .flow-step.sortable-ghost {
            opacity: 0.5;
        }
        
        .flow-step .drag-handle {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            background: #007bff;
            color: white;
            width: 20px;
            height: 40px;
            border-radius: 0 10px 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: move;
        }
        
        .flow-arrow {
            text-align: center;
            color: #007bff;
            font-size: 1.5rem;
            margin: 0.5rem 0;
        }
        
        .service-point-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 20px;
            font-weight: 500;
            margin: 0.25rem;
        }
        
        .optional-badge {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        }
        
        .flow-visualization {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .flow-node {
            background: white;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin: 0.5rem;
            min-width: 120px;
        }
        
        .flow-node.optional {
            border-style: dashed;
            border-color: #28a745;
        }
        
        .flow-connector {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>
                        จัดการระบบ
                    </h5>
                    
                    <?php include 'nav.php'; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>จัดการ Service Flow</h2>
                            <p class="text-muted">กำหนดเส้นทางการให้บริการสำหรับแต่ละประเภทคิว</p>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#copyFlowModal">
                                <i class="fas fa-copy me-2"></i>คัดลอก Flow
                            </button>
                            <button class="btn btn-success" onclick="previewFlow()">
                                <i class="fas fa-eye me-2"></i>ดูตัวอย่าง
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Queue Type Selection -->
                    <div class="content-card">
                        <h5 class="mb-4">เลือกประเภทคิว</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">ประเภทคิว</label>
                                <select class="form-select" id="queueTypeSelect" onchange="loadServiceFlow()">
                                    <option value="">-- เลือกประเภทคิว --</option>
                                    <?php foreach ($queueTypes as $qt): ?>
                                        <option value="<?php echo $qt['queue_type_id']; ?>">
                                            <?php echo htmlspecialchars($qt['type_name']); ?> (<?php echo $qt['prefix_char']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button class="btn btn-primary" onclick="addFlowStep()">
                                        <i class="fas fa-plus me-2"></i>เพิ่มขั้นตอน
                                    </button>
                                    <button class="btn btn-outline-danger ms-2" onclick="clearFlow()">
                                        <i class="fas fa-trash me-2"></i>ล้างทั้งหมด
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Flow Configuration -->
                    <div class="content-card" id="flowConfigCard" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">กำหนด Service Flow</h5>
                            <div>
                                <small class="text-muted">ลากเพื่อเรียงลำดับ</small>
                                <i class="fas fa-arrows-alt ms-2 text-muted"></i>
                            </div>
                        </div>
                        
                        <form method="POST" id="serviceFlowForm">
                            <input type="hidden" name="action" value="save_flow">
                            <input type="hidden" name="queue_type_id" id="selectedQueueTypeId">
                            
                            <div id="flowSteps">
                                <!-- Flow steps will be added here -->
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>บันทึก Service Flow
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Flow Visualization -->
                    <div class="content-card" id="flowVisualization" style="display: none;">
                        <h5 class="mb-4">ตัวอย่าง Service Flow</h5>
                        <div id="flowPreview">
                            <!-- Flow preview will be shown here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy Flow Modal -->
    <div class="modal fade" id="copyFlowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="copy_flow">
                    <div class="modal-header">
                        <h5 class="modal-title">คัดลอก Service Flow</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">คัดลอกจากประเภทคิว</label>
                            <select class="form-select" name="from_queue_type_id" required>
                                <option value="">-- เลือกประเภทคิว --</option>
                                <?php foreach ($queueTypes as $qt): ?>
                                    <option value="<?php echo $qt['queue_type_id']; ?>">
                                        <?php echo htmlspecialchars($qt['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ไปยังประเภทคิว</label>
                            <select class="form-select" name="to_queue_type_id" required>
                                <option value="">-- เลือกประเภทคิว --</option>
                                <?php foreach ($queueTypes as $qt): ?>
                                    <option value="<?php echo $qt['queue_type_id']; ?>">
                                        <?php echo htmlspecialchars($qt['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            การคัดลอกจะเขียนทับ Service Flow เดิมของประเภทคิวปลายทาง
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">คัดลอก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        let flowStepCounter = 0;
        let sortable;
        
        const servicePoints = <?php echo json_encode($servicePoints); ?>;
        
        function loadServiceFlow() {
            const queueTypeId = $('#queueTypeSelect').val();
            
            if (!queueTypeId) {
                $('#flowConfigCard').hide();
                $('#flowVisualization').hide();
                return;
            }
            
            $('#selectedQueueTypeId').val(queueTypeId);
            $('#flowConfigCard').show();
            
            // Load existing flow
            $.get('api/get_service_flows.php', { queue_type_id: queueTypeId }, function(data) {
                $('#flowSteps').empty();
                flowStepCounter = 0;
                
                if (data.length > 0) {
                    data.forEach(function(flow) {
                        addFlowStep(flow);
                    });
                } else {
                    addFlowStep();
                }
                
                initializeSortable();
                updateFlowPreview();
            });
        }
        
        function addFlowStep(flowData = null) {
            flowStepCounter++;
            
            const stepHtml = `
                <div class="flow-step" data-step="${flowStepCounter}">
                    <div class="drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <div class="text-center">
                                <strong class="step-number">${flowStepCounter}</strong>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">จากจุดบริการ</label>
                            <select class="form-select" name="flows[${flowStepCounter}][from_service_point_id]">
                                <option value="">-- เริ่มต้น --</option>
                                ${servicePoints.map(sp => 
                                    `<option value="${sp.service_point_id}" ${flowData && flowData.from_service_point_id == sp.service_point_id ? 'selected' : ''}>
                                        ${sp.point_name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ไปจุดบริการ *</label>
                            <select class="form-select" name="flows[${flowStepCounter}][to_service_point_id]" required>
                                <option value="">-- เลือกจุดบริการ --</option>
                                ${servicePoints.map(sp => 
                                    `<option value="${sp.service_point_id}" ${flowData && flowData.to_service_point_id == sp.service_point_id ? 'selected' : ''}>
                                        ${sp.point_name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">ลำดับ</label>
                            <input type="number" class="form-control" name="flows[${flowStepCounter}][sequence_order]" 
                                   value="${flowData ? flowData.sequence_order : flowStepCounter}" min="1">
                        </div>
                        
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFlowStep(${flowStepCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="flows[${flowStepCounter}][is_optional]" 
                                       ${flowData && flowData.is_optional ? 'checked' : ''}>
                                <label class="form-check-label">
                                    ขั้นตอนเสริม (ไม่บังคับ)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#flowSteps').append(stepHtml);
            
            if (sortable) {
                sortable.destroy();
            }
            initializeSortable();
            updateFlowPreview();
        }
        
        function removeFlowStep(stepId) {
            $(`.flow-step[data-step="${stepId}"]`).remove();
            updateStepNumbers();
            updateFlowPreview();
        }
        
        function clearFlow() {
            if (confirm('คุณต้องการล้าง Service Flow ทั้งหมดหรือไม่?')) {
                $('#flowSteps').empty();
                flowStepCounter = 0;
                updateFlowPreview();
            }
        }
        
        function updateStepNumbers() {
            $('#flowSteps .flow-step').each(function(index) {
                $(this).find('.step-number').text(index + 1);
            });
        }
        
        function initializeSortable() {
            const flowStepsEl = document.getElementById('flowSteps');
            if (flowStepsEl) {
                sortable = Sortable.create(flowStepsEl, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function() {
                        updateStepNumbers();
                        updateSequenceOrder();
                        updateFlowPreview();
                    }
                });
            }
        }
        
        function updateSequenceOrder() {
            $('#flowSteps .flow-step').each(function(index) {
                $(this).find('input[name*="[sequence_order]"]').val(index + 1);
            });
        }
        
        function updateFlowPreview() {
            const steps = [];
            
            $('#flowSteps .flow-step').each(function() {
                const fromId = $(this).find('select[name*="[from_service_point_id]"]').val();
                const toId = $(this).find('select[name*="[to_service_point_id]"]').val();
                const isOptional = $(this).find('input[name*="[is_optional]"]').is(':checked');
                
                if (toId) {
                    const fromPoint = servicePoints.find(sp => sp.service_point_id == fromId);
                    const toPoint = servicePoints.find(sp => sp.service_point_id == toId);
                    
                    steps.push({
                        from: fromPoint ? fromPoint.point_name : 'เริ่มต้น',
                        to: toPoint ? toPoint.point_name : '',
                        isOptional: isOptional
                    });
                }
            });
            
            if (steps.length > 0) {
                let previewHtml = '<div class="d-flex flex-wrap align-items-center justify-content-center">';
                
                steps.forEach((step, index) => {
                    if (index > 0) {
                        previewHtml += '<div class="flow-connector"><i class="fas fa-arrow-right"></i></div>';
                    }
                    
                    previewHtml += `
                        <div class="flow-node ${step.isOptional ? 'optional' : ''}">
                            <strong>${step.to}</strong>
                            ${step.isOptional ? '<br><small class="text-success">เสริม</small>' : ''}
                        </div>
                    `;
                });
                
                previewHtml += '</div>';
                
                $('#flowPreview').html(previewHtml);
                $('#flowVisualization').show();
            } else {
                $('#flowVisualization').hide();
            }
        }
        
        function previewFlow() {
            updateFlowPreview();
            if ($('#flowVisualization').is(':visible')) {
                $('html, body').animate({
                    scrollTop: $('#flowVisualization').offset().top - 100
                }, 500);
            }
        }
        
        // Initialize on page load
        $(document).ready(function() {
            // Handle form changes
            $(document).on('change', '#flowSteps select, #flowSteps input', function() {
                updateFlowPreview();
            });
        });
    </script>
</body>
</html>
