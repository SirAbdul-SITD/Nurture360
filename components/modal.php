<?php
/**
 * Reusable Modal Component
 * 
 * @param string $id Modal ID
 * @param string $title Modal title
 * @param string $content Modal content (HTML)
 * @param array $options Modal options
 */
function renderModal($id, $title, $content, $options = []) {
    $defaults = [
        'size' => 'medium', // small, medium, large, xlarge
        'closeable' => true,
        'backdrop' => true,
        'animation' => 'fade', // fade, slide, zoom
        'class' => '',
        'footer' => '',
        'onClose' => '',
        'onOpen' => ''
    ];
    $options = array_merge($defaults, $options);
    
    $sizeClass = 'modal-' . $options['size'];
    $animationClass = 'modal-' . $options['animation'];
    $modalClass = trim("modal {$sizeClass} {$animationClass} {$options['class']}");
    
    echo '<div id="' . $id . '" class="' . $modalClass . '" tabindex="-1" role="dialog" aria-labelledby="' . $id . '-title" aria-hidden="true">';
    
    if ($options['backdrop']) {
        echo '<div class="modal-backdrop" data-dismiss="modal"></div>';
    }
    
    echo '<div class="modal-dialog" role="document">';
    echo '<div class="modal-content">';
    
    // Header
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title" id="' . $id . '-title">' . htmlspecialchars($title) . '</h5>';
    if ($options['closeable']) {
        echo '<button type="button" class="modal-close" data-dismiss="modal" aria-label="Close" onclick="if(window.closeModal' . ucfirst($id) . '){window.closeModal' . ucfirst($id) . '();}else{var m=document.getElementById(\'' . $id . '\'); if(m){m.classList.remove(\'show\'); document.body.classList.remove(\'modal-open\');}}">';
        echo '<i class="fas fa-times"></i>';
        echo '</button>';
    }
    echo '</div>';
    
    // Body
    echo '<div class="modal-body">';
    echo $content;
    echo '</div>';
    
    // Footer
    if ($options['footer']) {
        echo '<div class="modal-footer">';
        echo $options['footer'];
        echo '</div>';
    }
    
    echo '</div>'; // modal-content
    echo '</div>'; // modal-dialog
    echo '</div>'; // modal
    
    // JavaScript for modal functionality
    echo '<script>';
    echo '(function(){';
    echo '  function init' . ucfirst($id) . '(){';
    echo '    const modal = document.getElementById("' . $id . '");';
    echo '    if (!modal) return;';
    echo '    // Open modal function';
    echo '    window.openModal' . ucfirst($id) . ' = function() {';
    echo '      modal.classList.add("show");';
    echo '      document.body.classList.add("modal-open");';
    echo '      modal.focus();';
    if ($options['onOpen']) {
        echo '      if (typeof ' . $options['onOpen'] . ' === "function") {';
        echo '        ' . $options['onOpen'] . '();';
        echo '      }';
    }
    echo '    };';
    echo '    // Close modal function';
    echo '    window.closeModal' . ucfirst($id) . ' = function() {';
    echo '      modal.classList.remove("show");';
    echo '      document.body.classList.remove("modal-open");';
    if ($options['onClose']) {
        echo '      if (typeof ' . $options['onClose'] . ' === "function") {';
        echo '        ' . $options['onClose'] . '();';
        echo '      }';
    }
    echo '    };';
    echo '    // Bind all elements that dismiss the modal (backdrop, cancel buttons, etc.)';
    echo '    const dismissers = modal.querySelectorAll("[data-dismiss=\"modal\"]");';
    echo '    dismissers.forEach(function(el){ el.addEventListener("click", function(){ window.closeModal' . ucfirst($id) . '(); }); });';
    echo '    // Also bind explicit close button if present';
    echo '    const closeBtn = modal.querySelector(".modal-close");';
    echo '    if (closeBtn) closeBtn.addEventListener("click", function(){ window.closeModal' . ucfirst($id) . '(); });';
    echo '    // Close on Escape key';
    echo '    document.addEventListener("keydown", function(e){ if (e.key === "Escape" && modal.classList.contains("show")) { window.closeModal' . ucfirst($id) . '(); }});';
    echo '  }';
    echo '  if (document.readyState === "loading") {';
    echo '    document.addEventListener("DOMContentLoaded", init' . ucfirst($id) . ');';
    echo '  } else {';
    echo '    init' . ucfirst($id) . '();';
    echo '  }';
    echo '})();';
    echo '</script>';
}

/**
 * Generate confirmation dialog modal
 */
function renderConfirmModal($id, $title, $message, $confirmText = 'Confirm', $cancelText = 'Cancel', $options = []) {
    $defaults = [
        'type' => 'danger', // success, warning, danger, info
        'onConfirm' => '',
        'confirmClass' => 'btn-danger',
        'cancelClass' => 'btn-outline'
    ];
    $options = array_merge($defaults, $options);
    
    $iconClass = 'fas fa-exclamation-triangle';
    switch ($options['type']) {
        case 'success':
            $iconClass = 'fas fa-check-circle';
            break;
        case 'warning':
            $iconClass = 'fas fa-exclamation-triangle';
            break;
        case 'danger':
            $iconClass = 'fas fa-exclamation-circle';
            break;
        case 'info':
            $iconClass = 'fas fa-info-circle';
            break;
    }
    
    $content = '
        <div class="confirm-dialog">
            <div class="confirm-icon">
                <i class="' . $iconClass . '"></i>
            </div>
            <div class="confirm-message">
                <p>' . htmlspecialchars($message) . '</p>
            </div>
        </div>';
    
    $footer = '
        <button type="button" class="btn ' . $options['cancelClass'] . '" data-dismiss="modal" onclick="if(window.closeModal' . ucfirst($id) . '){window.closeModal' . ucfirst($id) . '();}else{var m=document.getElementById(\'' . $id . '\'); if(m){m.classList.remove(\'show\'); document.body.classList.remove(\'modal-open\');}}}">
            ' . htmlspecialchars($cancelText) . '
        </button>
        <button type="button" class="btn ' . $options['confirmClass'] . '" onclick="handleConfirm' . ucfirst($id) . '()">
            ' . htmlspecialchars($confirmText) . '
        </button>';
    
    $modalOptions = [
        'size' => 'small',
        'footer' => $footer,
        'onConfirm' => $options['onConfirm']
    ];
    
    renderModal($id, $title, $content, $modalOptions);
    
    // Add confirmation handler
    echo '<script>';
    echo 'function handleConfirm' . ucfirst($id) . '() {';
    if ($options['onConfirm']) {
        echo '    if (typeof ' . $options['onConfirm'] . ' === "function") {';
        echo '        ' . $options['onConfirm'] . '();';
        echo '    }';
    }
    echo '    window.closeModal' . ucfirst($id) . '();';
    echo '}';
    echo '</script>';
}

/**
 * Generate form modal
 */
function renderFormModal($id, $title, $formContent, $submitText = 'Submit', $cancelText = 'Cancel', $options = []) {
    $defaults = [
        'size' => 'medium',
        'formId' => $id . 'Form',
        'onSubmit' => '',
        'submitClass' => 'btn-primary',
        'cancelClass' => 'btn-outline'
    ];
    $options = array_merge($defaults, $options);
    
    $footer = '
        <button type="button" class="btn ' . $options['cancelClass'] . '" data-dismiss="modal" onclick="if(window.closeModal' . ucfirst($id) . '){window.closeModal' . ucfirst($id) . '();}else{var m=document.getElementById(\'' . $id . '\'); if(m){m.classList.remove(\'show\'); document.body.classList.remove(\'modal-open\');}}}">
            ' . htmlspecialchars($cancelText) . '
        </button>
        <button type="submit" form="' . $options['formId'] . '" class="btn ' . $options['submitClass'] . '">
            ' . htmlspecialchars($submitText) . '
        </button>';
    
    $modalOptions = [
        'size' => $options['size'],
        'footer' => $footer,
        'onSubmit' => $options['onSubmit']
    ];
    
    renderModal($id, $title, $formContent, $modalOptions);
}
?> 