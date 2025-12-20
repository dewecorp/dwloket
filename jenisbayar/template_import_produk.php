<?php
/**
 * Template Import Produk
 * Script untuk membuat file Excel template yang bisa digunakan untuk import produk
 * Format: Kode, Produk, Harga, Status
 * Nama Sheet = Kategori (akan otomatis di-mapping ke jenis bayar)
 */

// Clean output buffer untuk memastikan tidak ada output sebelum header
while (ob_get_level()) {
    ob_end_clean();
}

// Cek apakah PhpSpreadsheet tersedia untuk Excel
$autoload_path = __DIR__ . '/../vendor/autoload.php';
$use_phpspreadsheet = false;

if (file_exists($autoload_path)) {
    try {
        require_once $autoload_path;
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') && class_exists('PhpOffice\PhpSpreadsheet\Writer\Xlsx')) {
            $use_phpspreadsheet = true;
        }
    } catch (Exception $e) {
        error_log("Template Error (Autoload): " . $e->getMessage());
    }
}

// Jika PhpSpreadsheet tersedia, buat Excel
if ($use_phpspreadsheet) {
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];

        // Data style
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];

        // Kategori dan sample data
        $categories = [
            'PULSA TELKOMSEL' => [
                ['T5', 'Telkomsel 5.000', 5500, 'Aktif'],
                ['T10', 'Telkomsel 10.000', 10838, 'Aktif'],
                ['T20', 'Telkomsel 20.000', 20800, 'Aktif'],
                ['T25', 'Telkomsel 25.000', 25800, 'Aktif'],
                ['T50', 'Telkomsel 50.000', 50800, 'Aktif'],
                ['T100', 'Telkomsel 100.000', 100800, 'Aktif'],
            ],
            'KUOTA SMARTFREN' => [
                ['SMDC30', 'Smart 30GB All + 60GB', 89000, 'Aktif'],
                ['SMDC50', 'Smart 50GB All + 100GB', 125000, 'Aktif'],
                ['SMDC100', 'Smart 100GB All + 200GB', 225000, 'Aktif'],
            ],
            'KUOTA AXIS' => [
                ['AXIS10', 'Axis 10GB', 35000, 'Aktif'],
                ['AXIS25', 'Axis 25GB', 65000, 'Aktif'],
                ['AXIS50', 'Axis 50GB', 120000, 'Aktif'],
            ],
            'KUOTA XL' => [
                ['XL10', 'XL 10GB', 35000, 'Aktif'],
                ['XL25', 'XL 25GB', 65000, 'Aktif'],
                ['XL50', 'XL 50GB', 120000, 'Aktif'],
            ],
            'KUOTA INDOSAT' => [
                ['IND10', 'Indosat 10GB', 35000, 'Aktif'],
                ['IND25', 'Indosat 25GB', 65000, 'Aktif'],
                ['IND50', 'Indosat 50GB', 120000, 'Aktif'],
            ],
            'KUOTA TELKOMSEL' => [
                ['TSEL10', 'Telkomsel 10GB', 35000, 'Aktif'],
                ['TSEL25', 'Telkomsel 25GB', 65000, 'Aktif'],
                ['TSEL50', 'Telkomsel 50GB', 120000, 'Aktif'],
            ],
            'TOKEN PLN' => [
                ['PLN20', 'Token PLN 20.000', 23000, 'Aktif'],
                ['PLN50', 'Token PLN 50.000', 55000, 'Aktif'],
                ['PLN100', 'Token PLN 100.000', 110000, 'Aktif'],
            ],
            'PULSA XL' => [
                ['XL5', 'XL 5.000', 5500, 'Aktif'],
                ['XL10', 'XL 10.000', 10838, 'Aktif'],
                ['XL25', 'XL 25.000', 25800, 'Aktif'],
            ],
            'PULSA AXIS' => [
                ['A10', 'Axis 10.000', 10838, 'Aktif'],
                ['A25', 'Axis 25.000', 25800, 'Aktif'],
                ['A50', 'Axis 50.000', 50800, 'Aktif'],
            ],
        ];

        $firstSheet = true;
        foreach ($categories as $categoryName => $sampleData) {
            if ($firstSheet) {
                // Gunakan sheet pertama yang sudah ada (default sheet)
                $sheet = $spreadsheet->getActiveSheet();
                $firstSheet = false;
            } else {
                // Buat sheet baru untuk kategori berikutnya
                $sheet = $spreadsheet->createSheet();
            }

            $sheet->setTitle($categoryName);

            // Set header
            $sheet->setCellValue('A1', 'Kode');
            $sheet->setCellValue('B1', 'Produk');
            $sheet->setCellValue('C1', 'Harga');
            $sheet->setCellValue('D1', 'Status');

            // Apply header style
            $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(40);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(12);

            // Add sample data
            $row = 2;
            foreach ($sampleData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->setCellValue('C' . $row, $data[2]);
                $sheet->setCellValue('D' . $row, $data[3]);

                // Apply data style
                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);

                // Format harga as number
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');

                $row++;
            }

            // Freeze first row
            $sheet->freezePane('A2');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Template_Import_Produk_' . date('Y-m-d') . '.xlsx';

        // Set headers untuk download Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (\Exception $e) {
        // Jika Excel gagal, tampilkan error detail untuk debugging
        $error_message = $e->getMessage();
        $error_file = $e->getFile();
        $error_line = $e->getLine();
        error_log("Template Excel Error: " . $error_message . " in " . $error_file . " on line " . $error_line);

        // Clean output buffer sebelum menampilkan error
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);
        die('Error: PhpSpreadsheet library tidak tersedia atau terjadi error.<br>Error Detail: ' . htmlspecialchars($error_message) . '<br>File: ' . htmlspecialchars($error_file) . '<br>Line: ' . $error_line);
    } catch (\Throwable $e) {
        // Catch semua throwable untuk PHP 7+
        $error_message = $e->getMessage();
        $error_file = $e->getFile();
        $error_line = $e->getLine();
        error_log("Template Excel Fatal Error: " . $error_message . " in " . $error_file . " on line " . $error_line);

        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);
        die('Error: PhpSpreadsheet library tidak tersedia atau terjadi error.<br>Error Detail: ' . htmlspecialchars($error_message) . '<br>File: ' . htmlspecialchars($error_file) . '<br>Line: ' . $error_line);
    }
}

// Jika PhpSpreadsheet tidak tersedia, tampilkan error
http_response_code(500);
die('Error: PhpSpreadsheet library tidak tersedia. Silakan install PhpSpreadsheet terlebih dahulu dengan menjalankan: composer require phpoffice/phpspreadsheet');
?>
