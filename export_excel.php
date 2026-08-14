<?php
require_once 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_baru.php");
    exit();
}

// Fetch Data Transaksi
$query = "SELECT orders.*, users.username 
          FROM orders 
          JOIN users ON orders.user_id = users.id 
          ORDER BY orders.id DESC";
$result = mysqli_query($conn, $query);

// Inisialisasi Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Transaksi');

// 1. Judul Laporan
$sheet->setCellValue('A1', 'LAPORAN TRANSAKSI PENJUALAN STOREAPP');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Tanggal Cetak: ' . date('d-m-Y H:i:s'));
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 2. Header Tabel
$headers = ['No', 'Kode Invoice', 'Nama Pelanggan', 'Tanggal Transaksi', 'Total Belanja (Rp)', 'Status'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '4', $header);
    $col++;
}

// Styling Header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F46E5']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];
$sheet->getStyle('A4:F4')->applyFromArray($headerStyle);

// 3. Isi Data
$row = 5;
$no = 1;
$totalOmset = 0;

if ($result && mysqli_num_rows($result) > 0) {
    while ($data = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $row, $no++);
        $sheet->setCellValue('B' . $row, $data['kode_invoice']);
        $sheet->setCellValue('C' . $row, $data['username']);
        
        $tanggal = !empty($data['tanggal_order']) ? date("d/m/Y H:i", strtotime($data['tanggal_order'])) : '-';
        $sheet->setCellValue('D' . $row, $tanggal);
        
        $sheet->setCellValue('E' . $row, $data['total_harga']);
        $sheet->setCellValue('F' . $row, $data['status']);

        // Akumulasi Omset
        if ($data['status'] !== 'Batal') {
            $totalOmset += $data['total_harga'];
        }

        // Format angka Rupiah
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        
        // Alignment
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
}

// 4. Baris Total Omset
$sheet->setCellValue('A' . $row, 'TOTAL OMSET (Excl. Batal)');
$sheet->mergeCells('A' . $row . ':D' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setCellValue('E' . $row, $totalOmset);
$sheet->getStyle('E' . $row)->getFont()->setBold(true);
$sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');

// Border Semua Tabel
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
];
$sheet->getStyle('A4:F' . $row)->applyFromArray($borderStyle);

// Auto-size kolom
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 5. Output Download File Excel
$filename = 'Laporan_Transaksi_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();