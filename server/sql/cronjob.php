<?php
require '../php/db_connection.php';

// Nur IDs selektieren, bei denen valid auf 1 gesetzt wird
$selectSql = "
SELECT id FROM kaeufer
WHERE valid <> 1
  AND checked = 1
  AND (
    submited > NOW() - INTERVAL 24 HOUR
    OR (submited <= NOW() - INTERVAL 24 HOUR AND (checked IS NULL OR checked != 1))
  )
";

$result = $conn->query($selectSql);
if (!$result) {
    error_log("Fehler beim Selektieren: " . $conn->error);
    exit(1);
}

$logIds = [];
while ($row = $result->fetch_assoc()) {
    $logIds[] = (int)$row['id'];
}
$result->free();

// UPDATE durchführen (alle, nicht nur checked = 1 – wie ursprünglich)
$updateSql = "
UPDATE kaeufer
SET valid = CASE
    WHEN checked = 1 THEN 1
    ELSE 0
END
WHERE valid <> 1
  AND (
    submited > NOW() - INTERVAL 24 HOUR
    OR (submited <= NOW() - INTERVAL 24 HOUR AND (checked IS NULL OR checked != 1))
  )
";

if ($conn->query($updateSql)) {
    echo "[" . date('Y-m-d H:i:s') . "] Update durchgeführt. Valid=1 für " . count($logIds) . " Käufer\n";
} else {
    error_log("Fehler beim Update: " . $conn->error);
    exit(1);
}

// Nur valid=1 Einträge loggen
if (!empty($logIds)) {
    $logStmt = $conn->prepare("
        INSERT INTO update_log (kaeufer_id, status, message)
        VALUES (?, 'success', 'valid auf 1 gesetzt')
    ");

    foreach ($logIds as $kaeuferId) {
        $logStmt->bind_param("i", $kaeuferId);
        $logStmt->execute();
    }

    $logStmt->close();
}

$conn->close();
?>