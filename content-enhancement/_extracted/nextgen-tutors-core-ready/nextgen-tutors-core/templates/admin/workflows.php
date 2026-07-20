<?php
/**
 * NextGen Tutors Workflow Builder
 */
?>
<div class="wrap ngt-admin-wrap">
    <div class="ngt-dashboard-header">
        <h1>Workflow Orchestrator <span class="badge">BETA</span></h1>
        <div class="ngt-quick-actions">
            <button id="save-workflow" class="button button-primary"><span class="dashicons dashicons-saved"></span> Save & Rewire</button>
        </div>
    </div>

    <div class="ngt-workflow-builder-container">
        <!-- Sidebar: Triggers & Actions -->
        <div class="ngt-workflow-sidebar">
            <div class="ngt-card">
                <h3>1. Choose Trigger</h3>
                <div class="ngt-trigger-list">
                    <?php foreach($triggers as $id => $label): ?>
                        <div class="ngt-draggable-item trigger" draggable="true" data-type="trigger" data-id="<?php echo $id; ?>">
                            <span class="dashicons dashicons-marker"></span>
                            <?php echo $label; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ngt-card">
                <h3>2. Available Actions</h3>
                <div class="ngt-action-list">
                    <?php foreach($actions as $id => $label): ?>
                        <div class="ngt-draggable-item action" draggable="true" data-type="action" data-id="<?php echo $id; ?>">
                            <span class="dashicons dashicons-plus"></span>
                            <?php echo $label; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Canvas -->
        <div class="ngt-workflow-canvas-wrap">
            <div class="ngt-card canvas-card">
                <h3>Workflow Canvas</h3>
                <div id="ngt-workflow-canvas" class="ngt-canvas">
                    <div class="canvas-placeholder">Drag a trigger here to start...</div>
                </div>
            </div>

            <div class="ngt-card config-card" id="step-config" style="display:none;">
                <h3>Step Configuration</h3>
                <div id="config-fields">
                    <!-- Dynamic fields based on selected step -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ngt-workflow-builder-container { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
.ngt-draggable-item { 
    background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; 
    margin-bottom: 10px; cursor: grab; font-size: 13px; display: flex; align-items: center; gap: 10px;
    transition: all 0.2s;
}
.ngt-draggable-item:hover { border-color: var(--ngt-primary); background: #fff; }
.ngt-draggable-item.trigger { border-left: 4px solid var(--ngt-accent); }
.ngt-draggable-item.action { border-left: 4px solid var(--ngt-secondary); }

.ngt-canvas {
    min-height: 500px; background: #fafafa; border: 2px dashed #ddd; border-radius: 12px;
    display: flex; flex-direction: column; align-items: center; padding: 40px; gap: 20px;
}
.canvas-placeholder { color: #999; margin-top: 200px; font-style: italic; }

.ngt-workflow-step {
    background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 12px;
    width: 300px; position: relative; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.ngt-workflow-step::after {
    content: '↓'; position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%);
    font-size: 20px; color: #ccc;
}
.ngt-workflow-step:last-child::after { display: none; }

.ngt-workflow-step.active { border-color: var(--ngt-primary); box-shadow: 0 0 0 2px rgba(0,102,204,0.1); }
</style>

<script>
(function($) {
    'use strict';
    
    $(function() {
        const $canvas = $('#ngt-workflow-canvas');
        let workflow = { id: 'wf_' + Date.now(), name: 'New Automation', trigger: null, steps: [] };

        $('.ngt-draggable-item').on('dragstart', function(e) {
            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                type: $(this).data('type'),
                id: $(this).data('id'),
                label: $(this).text().trim()
            }));
        });

        $canvas.on('dragover', function(e) { e.preventDefault(); });

        $canvas.on('drop', function(e) {
            e.preventDefault();
            const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
            
            if (data.type === 'trigger') {
                if (workflow.trigger) {
                    alert('Only one trigger per workflow allowed.');
                    return;
                }
                workflow.trigger = data.id;
                $canvas.find('.canvas-placeholder').remove();
            } else {
                if (!workflow.trigger) {
                    alert('Please drag a trigger first.');
                    return;
                }
                workflow.steps.push({ type: data.id, config: {} });
            }

            renderCanvas();
        });

        function renderCanvas() {
            $canvas.empty();
            
            if (workflow.trigger) {
                $canvas.append(`<div class="ngt-workflow-step trigger"><strong>Trigger:</strong> ${workflow.trigger}</div>`);
            }

            workflow.steps.forEach((step, index) => {
                $canvas.append(`<div class="ngt-workflow-step action" data-index="${index}"><strong>Action:</strong> ${step.type}</div>`);
            });
        }

        $('#save-workflow').on('click', function() {
            if (!workflow.trigger || workflow.steps.length === 0) {
                alert('Please complete the workflow before saving.');
                return;
            }

            $.ajax({
                url: ngtSettings.rest_url + 'ngt/v1/workflows',
                method: 'POST',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', ngtSettings.rest_nonce),
                data: JSON.stringify(workflow),
                contentType: 'application/json',
                success: (response) => {
                    alert('Workflow Saved & System Rewired Successfully!');
                }
            });
        });
    });
})(jQuery);
</script>

