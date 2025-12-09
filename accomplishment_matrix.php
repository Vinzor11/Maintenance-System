<?php
require_once('fpdf.php');

$msg = '';

// 25 blank rows for the admin to fill in; change 25 to your needed row count
$tableRows = array_fill(0, 25, "");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    $name = isset($_POST['building']) ? trim($_POST['building']) : '';
    $num_rooms = isset($_POST['num_rooms']) ? trim($_POST['num_rooms']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    $pdf = new FPDF('L');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,'MONTHLY MAINTENANCE PLAN AND INSPECTION REPORT',0,1,'C');
    $pdf->SetFont('Arial','',10);

    // Header/info section
    $pdf->Cell(40,8,"Name of Building:",0,0);   $pdf->Cell(50,8,$name,0,0);
    $pdf->Cell(40,8,"No. of Rooms:",0,0);       $pdf->Cell(20,8,$num_rooms,0,0);
    $pdf->Cell(40,8,"Category:",0,0);           $pdf->Cell(30,8,$category,0,1);
    $pdf->Cell(40,8,"Date:",0,0);               $pdf->Cell(50,8,$date,0,1);
    $pdf->Cell(40,8,"Remarks:",0,0);            $pdf->Cell(100,8,$remarks,0,1);

    $pdf->Ln(4);

    // Table header
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(10,8,"No.",1,0,'C');
    $pdf->Cell(65,8,"Description",1,0,'C');
    $pdf->Cell(30,8,"In good",1,0,'C');
    $pdf->Cell(30,8,"Not in good",1,0,'C');
    $pdf->Cell(80,8,"Remarks",1,1,'C');

    $pdf->SetFont('Arial','',10);
    foreach($tableRows as $i => $desc) {
        $descInput = isset($_POST["desc_$i"]) ? $_POST["desc_$i"] : "";
        $goodBox = isset($_POST["good_$i"]) ? "V" : "";
        $notgoodBox = isset($_POST["notgood_$i"]) ? "V" : "";
        $rowRemarks = isset($_POST["remark_$i"]) ? trim($_POST["remark_$i"]) : "";
        $pdf->Cell(10,8,$i+1,1,0,'C');
        $pdf->Cell(65,8,$descInput,1,0);
        $pdf->Cell(30,8,$goodBox,1,0,'C');
        $pdf->Cell(30,8,$notgoodBox,1,0,'C');
        $pdf->Cell(80,8,$rowRemarks,1,1);
    }

    $folder = __DIR__ . '/generated/';
    if (!file_exists($folder)) mkdir($folder, 0777, true);
    $safeFile = preg_replace('/[^A-Za-z0-9_-]/', '_', $name . '_' . $date);
    $path = $folder . $safeFile . '.pdf';
    $pdf->Output('F', $path);
    $msg = "PDF generated and saved to: $path";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Table PDF Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Admin Dashboard</a>
<div class="container" style="max-width:1100px;margin-top:30px;">
    <h2>Maintenance Plan Report Form</h2>
    <?php if ($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="row mb-2">
            <div class="col"><label>Name of Building</label><input type="text" name="building" class="form-control" required></div>
            <div class="col"><label>No. of Rooms</label><input type="text" name="num_rooms" class="form-control" required></div>
            <div class="col"><label>Category</label><input type="text" name="category" class="form-control" required></div>
            <div class="col"><label>Date</label><input type="date" name="date" class="form-control" required></div>
        </div>
        <div class="mb-2"><label>General Remarks</label><input type="text" name="remarks" class="form-control"></div>
        <hr>
        <h4>Inspection Table</h4>
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Description</th>
                    <th>In good<br>condition</th>
                    <th>Not in good<br>condition</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($tableRows as $i => $desc): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><input type="text" name="desc_<?= $i ?>" value="<?= isset($_POST["desc_$i"]) ? htmlspecialchars($_POST["desc_$i"]) : '' ?>" class="form-control"></td>
                    <td><input type="checkbox" name="good_<?= $i ?>" <?= isset($_POST["good_$i"]) ? "checked" : "" ?>></td>
                    <td><input type="checkbox" name="notgood_<?= $i ?>" <?= isset($_POST["notgood_$i"]) ? "checked" : "" ?>></td>
                    <td><input type="text" name="remark_<?= $i ?>" value="<?= isset($_POST["remark_$i"]) ? htmlspecialchars($_POST["remark_$i"]) : '' ?>" class="form-control"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="generate_pdf" class="btn btn-success">Generate PDF</button>
    </form>
</div>
</body>
</html>
