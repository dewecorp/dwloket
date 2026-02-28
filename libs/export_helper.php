<?php
/**
 * Export Helper Functions
 * Provides functions to export data to Excel (CSV) and PDF
 */

/**
 * Export data to Excel (CSV format)
 * @param array $headers Column headers
 * @param array $data Data rows
 * @param string $filename Output filename
 */
function exportToExcel($headers, $data, $filename = 'export') {
    // Set headers for Excel
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Add BOM for UTF-8 Excel compatibility
    echo "\xEF\xBB\xBF";

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $headers);

    // Write data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Export data to PDF
 * @param string $title PDF title
 * @param array $headers Column headers
 * @param array $data Data rows
 * @param string $filename Output filename
 */
function exportToPDF($title, $headers, $data, $filename = 'export') {
    require_once __DIR__ . '/../config/config.php';

    // Simple HTML to PDF using browser print
    // For better PDF, you can use TCPDF or mPDF library

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body { margin: 0; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <h2>' . htmlspecialchars($title) . '</h2>
    <p style="text-align: right; margin-bottom: 10px;">Tanggal: ' . date('d F Y, H:i:s') . '</p>
    <table>
        <thead>
            <tr>';

    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }

    $html .= '</tr>
        </thead>
        <tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody>
    </table>
    <div class="footer">
        <p>Dicetak pada: ' . date('d F Y, H:i:s') . '</p>
        <p>Supported by OrderKuota</p>
    </div>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '_' . date('Y-m-d') . '.html"');
    echo $html;
    exit;
}

/**
 * Get export URL with current filters
 * @param string $type Export type (excel or pdf)
 * @param string $page Current page identifier
 * @param array $params Current filter parameters
 * @return string Export URL
 */
function getExportUrl($type, $page, $params = []) {
    $base_url = 'export_' . $type . '.php?page=' . urlencode($page);
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $base_url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    return $base_url;
}





