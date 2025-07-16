<?php
ob_start();
require __DIR__ . '/certificates_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates Earned</title>
    <link rel="stylesheet" href="css/reports-main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/date-range-picker.css">
    <link rel="stylesheet" href="css/reports-data.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/organization-search.css">
    <link rel="stylesheet" href="css/district-search.css">
    <link rel="stylesheet" href="css/reports-messaging.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../css/print.css" media="print">
    <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="../lib/message-dismissal.js"></script>
</head>
<body>
    <main>
    <?php if (!$validRange): ?>
        <div class="error-message" role="alert" style="color:#b00; font-weight:bold; margin:2em 0;">A valid date range must be selected on the main reports page.</div>
    <?php else: ?>
    <div class="table-responsive">
        <button id="print-certificates-list" class="print-button no-print" onclick="window.print()">Print</button>
        <table id="certificate-data">
            <caption>Certificate Earners | <?= htmlspecialchars($start) ?> to <?= htmlspecialchars($end) ?></caption>
            <thead>
                <tr>
                    <th scope="col">Cohort</th>
                    <th scope="col">Year</th>
                    <th scope="col">First</th>
                    <th scope="col">Last</th>
                    <th scope="col">Email</th>
                    <th scope="col">Organization</th>
                    <th scope="col">Issued</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($filtered)): ?>
                <tr><td colspan="7">No certificates issued in this range.</td></tr>
            <?php else: ?>
                <?php foreach ($filtered as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row[3] ?? '') ?></td>
                    <td><?= htmlspecialchars($row[4] ?? '') ?></td>
                    <td><?= htmlspecialchars($row[5] ?? '') ?></td>
                    <td><?= htmlspecialchars($row[6] ?? '') ?></td>
                    <td><?= htmlspecialchars($row[7] ?? '') ?></td>
                    <td><?= htmlspecialchars(isset($row[9]) ? abbreviateLinkText($row[9]) : '') ?></td>
                    <td><?= htmlspecialchars($row[11] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    </main>
</body>
</html>
<?php
echo ob_get_clean();
