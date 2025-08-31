<?php
/**
 * Reusable Table Component
 * 
 * @param array $columns Array of column definitions with keys: key, label, sortable, width
 * @param array $data Array of data rows
 * @param array $options Table options (sortable, pagination, search, etc.)
 */
function renderTable($columns, $data, $options = []) {
    $defaults = [
        'sortable' => true,
        'pagination' => false,
        'search' => false,
        'actions' => true,
        'responsive' => true,
        'class' => '',
        'id' => 'dataTable'
    ];
    $options = array_merge($defaults, $options);
    
    $tableId = $options['id'];
    $sortableClass = $options['sortable'] ? 'sortable-table' : '';
    $responsiveClass = $options['responsive'] ? 'table-responsive' : '';
    $tableClass = trim("table {$sortableClass} {$responsiveClass} {$options['class']}");
    
    // Generate search HTML if enabled
    $searchHtml = '';
    if ($options['search']) {
        $searchHtml = '
        <div class="table-search">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="table-search-input" placeholder="Search..." data-table="' . $tableId . '">
            </div>
        </div>';
    }
    
    // Generate pagination HTML if enabled
    $paginationHtml = '';
    if ($options['pagination'] && isset($options['pagination_data'])) {
        $paginationHtml = generatePaginationHtml($options['pagination_data'], $tableId);
    }
    
    echo '<div class="table-container" data-table-id="' . $tableId . '">';
    
    if ($searchHtml) {
        echo $searchHtml;
    }
    
    echo '<div class="table-wrapper">';
    echo '<table class="' . $tableClass . '" id="' . $tableId . '">';
    echo '<thead>';
    echo '<tr>';
    
    foreach ($columns as $column) {
        $sortable = isset($column['sortable']) ? $column['sortable'] : $options['sortable'];
        $width = isset($column['width']) ? ' style="width: ' . $column['width'] . '"' : '';
        $sortClass = $sortable ? 'sortable' : '';
        $sortAttr = $sortable ? ' data-sort="' . $column['key'] . '"' : '';
        
        echo '<th class="' . $sortClass . '"' . $sortAttr . $width . '>';
        echo '<div class="th-content">';
        echo '<span>' . htmlspecialchars($column['label']) . '</span>';
        if ($sortable) {
            echo '<i class="fas fa-sort sort-icon"></i>';
        }
        echo '</div>';
        echo '</th>';
    }
    
    if ($options['actions']) {
        echo '<th class="actions-column">Actions</th>';
    }
    
    echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    if (!empty($data)) {
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($columns as $column) {
                $value = $row[$column['key']] ?? '';
                $formatted = isset($column['format']) ? call_user_func($column['format'], $value, $row) : $value;
                $class = isset($column['class']) ? ' class="' . $column['class'] . '"' : '';
                $dataAttr = isset($column['data_attr']) ? ' data-' . $column['data_attr'] . '="' . htmlspecialchars($value) . '"' : '';
                
                echo '<td' . $class . $dataAttr . '>' . $formatted . '</td>';
            }
            
            if ($options['actions']) {
                echo '<td class="actions-cell">';
                if (isset($options['action_buttons'])) {
                    echo generateActionButtons($options['action_buttons'], $row);
                }
                echo '</td>';
            }
            
            echo '</tr>';
        }
    } else {
        $colspan = count($columns) + ($options['actions'] ? 1 : 0);
        echo '<tr><td colspan="' . $colspan . '" class="no-data">No data available</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    if ($paginationHtml) {
        echo $paginationHtml;
    }
    
    echo '</div>';
}

/**
 * Generate action buttons for table rows
 */
function generateActionButtons($buttons, $row) {
    $html = '<div class="action-buttons">';
    
    foreach ($buttons as $button) {
        $type = $button['type'] ?? 'link';
        $icon = $button['icon'] ?? '';
        $label = $button['label'] ?? '';
        $class = $button['class'] ?? 'btn btn-sm btn-outline';
        $tooltip = isset($button['tooltip']) ? ' data-tooltip="' . htmlspecialchars($button['tooltip']) . '"' : '';
        
        if ($type === 'button') {
            $onclick = $button['onclick'] ?? '';
            $onclick = str_replace('{id}', $row['id'], $onclick);
            $onclick = str_replace('{data}', json_encode($row), $onclick);
            
            echo '<button type="button" class="' . $class . '" onclick="' . $onclick . '"' . $tooltip . '>';
            if ($icon) echo '<i class="' . $icon . '"></i>';
            if ($label) echo ' ' . htmlspecialchars($label);
            echo '</button>';
        } else {
            $href = $button['href'] ?? '#';
            $href = str_replace('{id}', $row['id'], $href);
            
            echo '<a href="' . $href . '" class="' . $class . '"' . $tooltip . '>';
            if ($icon) echo '<i class="' . $icon . '"></i>';
            if ($label) echo ' ' . htmlspecialchars($label);
            echo '</a>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate pagination HTML
 */
function generatePaginationHtml($paginationData, $tableId) {
    $currentPage = $paginationData['current_page'] ?? 1;
    $totalPages = $paginationData['total_pages'] ?? 1;
    $totalRecords = $paginationData['total_records'] ?? 0;
    $perPage = $paginationData['per_page'] ?? 10;
    
    $startRecord = (($currentPage - 1) * $perPage) + 1;
    $endRecord = min($currentPage * $perPage, $totalRecords);
    
    $html = '<div class="table-pagination">';
    $html .= '<div class="pagination-info">';
    $html .= '<span>Showing ' . $startRecord . ' to ' . $endRecord . ' of ' . $totalRecords . ' entries</span>';
    $html .= '</div>';
    
    if ($totalPages > 1) {
        $html .= '<div class="pagination-controls">';
        
        // Previous button
        if ($currentPage > 1) {
            $html .= '<a href="?page=' . ($currentPage - 1) . '" class="pagination-btn" data-page="' . ($currentPage - 1) . '">';
            $html .= '<i class="fas fa-chevron-left"></i>';
            $html .= '</a>';
        }
        
        // Page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        if ($startPage > 1) {
            $html .= '<a href="?page=1" class="pagination-btn" data-page="1">1</a>';
            if ($startPage > 2) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
        }
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $activeClass = $i === $currentPage ? ' active' : '';
            $html .= '<a href="?page=' . $i . '" class="pagination-btn' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
            $html .= '<a href="?page=' . $totalPages . '" class="pagination-btn" data-page="' . $totalPages . '">' . $totalPages . '</a>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $html .= '<a href="?page=' . ($currentPage + 1) . '" class="pagination-btn" data-page="' . ($currentPage + 1) . '">';
            $html .= '<i class="fas fa-chevron-right"></i>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}
?> 