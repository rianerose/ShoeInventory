<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
require_once __DIR__ . '/../config/db.php';

// determine period: weekly (last 7 days) or monthly (current month)
$period = isset($_GET['period']) && $_GET['period'] === 'monthly' ? 'monthly' : 'weekly';
$export = isset($_GET['export']) && $_GET['export']=='1';

if ($period === 'weekly') {
    // last 7 days
    $sales_stmt = $conn->prepare("
        SELECT DATE(s.created_at) AS day,
               IFNULL(SUM(s.total_amount),0) AS sales,
               IFNULL(SUM(s.quantity * p.cost),0) AS cogs
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(s.created_at)
        ORDER BY DATE(s.created_at)
    ");
} else {
    // current month - group by day
    $sales_stmt = $conn->prepare("
        SELECT DATE(s.created_at) AS day,
               IFNULL(SUM(s.total_amount),0) AS sales,
               IFNULL(SUM(s.quantity * p.cost),0) AS cogs
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())
        GROUP BY DATE(s.created_at)
        ORDER BY DATE(s.created_at)
    ");
}

$sales_stmt->execute();
$rows = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// total quantity sold for the period
$q_stmt = $conn->prepare("SELECT IFNULL(SUM(quantity),0) AS total_qty FROM sales s WHERE " .
    ($period === 'weekly' ? "DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)" : "YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())")
);
$q_stmt->execute();
$q_row = $q_stmt->fetch(PDO::FETCH_ASSOC);
$total_quantity_sold = (int)$q_row['total_qty'];

// build full days list to include days with zero
$labels = [];
$data_sales = [];
$data_cogs = [];
$today = new DateTime();
if ($period === 'weekly') {
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime())->sub(new DateInterval('P'.$i.'D'))->format('Y-m-d');
        $labels[$d] = ['sales'=>0,'cogs'=>0];
    }
} else {
    // current month days
    $start = new DateTime(date('Y-m-01'));
    $end = new DateTime($start->format('Y-m-t'));
    $interval = new DateInterval('P1D');
    for ($dt = $start; $dt <= $end; $dt->add($interval)) {
        $d = $dt->format('Y-m-d');
        $labels[$d] = ['sales'=>0,'cogs'=>0];
    }
}

foreach ($rows as $r) {
    $day = $r['day'];
    if (!isset($labels[$day])) {
        $labels[$day] = ['sales'=>0,'cogs'=>0];
    }
    $labels[$day]['sales'] = (float)$r['sales'];
    $labels[$day]['cogs'] = (float)$r['cogs'];
}

// prepare arrays
$dates = [];
$sales_arr = [];
$cogs_arr = [];
$profit_arr = [];
$total_sales = 0;
$total_cogs = 0;
foreach ($labels as $d => $vals) {
    $dates[] = $d;
    $sales_arr[] = $vals['sales'];
    $cogs_arr[] = $vals['cogs'];
    $profit = $vals['sales'] - $vals['cogs'];
    $profit_arr[] = $profit;
    $total_sales += $vals['sales'];
    $total_cogs += $vals['cogs'];
}
$total_profit = $total_sales - $total_cogs;

if ($export) {
    // export CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_'.$period.'_.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['date','sales','cogs','profit']);
    foreach ($dates as $i => $d) {
        fputcsv($out, [$d, number_format($sales_arr[$i],2,'.',''), number_format($cogs_arr[$i],2,'.',''), number_format($profit_arr[$i],2,'.','')]);
    }
    fputcsv($out, ['TOTAL', number_format($total_sales,2,'.',''), number_format($total_cogs,2,'.',''), number_format($total_profit,2,'.','')]);
    fclose($out);
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Reports - <?= ucfirst($period) ?></title>
<link rel="stylesheet" href="../style.css">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-y..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<link rel="stylesheet" href="style_reports.css">
</head>
<body>
<a href="../dashboard.php" class="btn btn-secondary" style="position:absolute;top:10px;right:10px;">Exit Reports</a>
<div class="container">
  <div class="header">
    <h2><i class="fa-solid fa-chart-line"></i> Reports - <?= ucfirst($period) ?></h2>
    <div class="controls">
      <a class="btn" href="reports.php?period=weekly"><i class="fa-solid fa-list"></i> Weekly</a>
      <a class="btn" href="reports.php?period=monthly"><i class="fa-solid fa-calendar"></i> Monthly</a>
      <a class="btn" href="reports.php?period=<?= $period ?>&export=1"><i class="fa-solid fa-file-csv"></i> CSV</a>
      <button id="exportExcel" class="btn"><i class="fa-solid fa-file-excel"></i> Excel</button>
      <button id="exportPDF" class="btn"><i class="fa-solid fa-file-pdf"></i> PDF</button>
    </div>
  </div>

  <div class="card kpis">
    <div class="kpi">
      <div class="icon"><i class="fa-solid fa-money-bill-wave"></i></div>
      <div>
        <div class="small">Total Sales</div>
        <div style="font-size:20px;font-weight:700;">₱ <?= number_format($total_sales,2) ?></div>
      </div>
    </div>
    <div class="kpi">
      <div class="icon"><i class="fa-solid fa-box-open"></i></div>
      <div>
        <div class="small">Total Cost (COGS)</div>
        <div style="font-size:20px;font-weight:700;">₱ <?= number_format($total_cogs,2) ?></div>
      </div>
    </div>
    <div class="kpi">
      <div class="icon"><i class="fa-solid fa-chart-pie"></i></div>
      <div>
        <div class="small">Profit</div>
        <div style="font-size:20px;font-weight:700;">₱ <?= number_format($total_profit,2) ?></div>
      </div>
    </div>
    <div class="kpi">
      <div class="icon"><i class="fa-solid fa-boxes-stacked"></i></div>
      <div>
        <div class="small">Total Quantity Sold</div>
        <div style="font-size:20px;font-weight:700;"><?php echo number_format($total_quantity_sold); ?></div>
      </div>
    </div>
  </div>

  <div class="card">
    <canvas id="barChart" height="120"></canvas>
  </div>

  <div class="card" style="display:flex;gap:20px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;">
      <canvas id="pieChart" height="220"></canvas>
    </div>
    <div style="flex:1;min-width:220px;">
      <h3>Daily Breakdown</h3>
      <table style="width:100%;border-collapse:collapse;color:var(--text);">
        <thead><tr><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)">Date</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)">Sales</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)">COGS</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)">Profit</th></tr></thead>
        <tbody>
        <?php foreach($dates as $i => $d): ?>
          <tr>
            <td style="padding:8px 6px;"><?= $d ?></td>
            <td style="padding:8px 6px;text-align:right;">₱ <?= number_format($sales_arr[$i],2) ?></td>
            <td style="padding:8px 6px;text-align:right;">₱ <?= number_format($cogs_arr[$i],2) ?></td>
            <td style="padding:8px 6px;text-align:right;">₱ <?= number_format($profit_arr[$i],2) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
const labels = <?= json_encode(array_values($dates)) ?>;
const salesData = <?= json_encode(array_values($sales_arr)) ?>;
const cogsData = <?= json_encode(array_values($cogs_arr)) ?>;
const profitData = <?= json_encode(array_values($profit_arr)) ?>;

const accent = '<?= "#00ADB5" ?>';
const card = '<?= "#393E46" ?>';
const text = '<?= "#EEEEEE" ?>';

const ctx = document.getElementById('barChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'Sales', data: salesData, stack: 'stack1', backgroundColor: accent },
            { label: 'COGS', data: cogsData, stack: 'stack1', backgroundColor: '#f39c12' }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { ticks: { color: text } },
            y: { ticks: { color: text } }
        },
        plugins: {
            legend: { labels: { color: text } }
        }
    }
});

const ctx2 = document.getElementById('pieChart').getContext('2d');
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: ['Profit','COGS'],
        datasets: [{
            data: [<?= json_encode($total_profit) ?>, <?= json_encode($total_cogs) ?>],
            backgroundColor: [accent, '#e76f51']
        }]
    },
    options: {
        plugins: {
            legend: { labels: { color: text } }
        }
    }
});

// Export to Excel (SheetJS)
document.getElementById('exportExcel').addEventListener('click', function(){
    var wb = XLSX.utils.book_new();
    var data = [['Date','Sales','COGS','Profit']];
    for(var i=0;i<labels.length;i++){
        data.push([labels[i], salesData[i], cogsData[i], profitData[i]]);
    }
    data.push(['TOTAL', <?= json_encode($total_sales) ?>, <?= json_encode($total_cogs) ?>, <?= json_encode($total_profit) ?>]);
    var ws = XLSX.utils.aoa_to_sheet(data);
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, 'report_<?= $period ?>.xlsx');
});

// Export to PDF (capture container)
document.getElementById('exportPDF').addEventListener('click', function(){
    const { jsPDF } = window.jspdf;
    const container = document.querySelector('.container');
    html2canvas(container, {scale:2}).then(canvas=>{
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p','mm','a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('report_<?= $period ?>.pdf');
    });
});

</script>

</body>
</html>
