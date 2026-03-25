<?php
// Quick script to update yarn type from "Feb-60" to "2/60"
$pdo = DB::conn();
$company = get_company();

// Update the yarn type name
$stmt = $pdo->prepare('UPDATE yarn_types SET name = ? WHERE company_id = ? AND name = ?');
$result = $stmt->execute(['2/60', $company['id'], 'Feb-60']);

if ($result && $stmt->rowCount() > 0) {
    echo "Successfully updated yarn type from 'Feb-60' to '2/60'";
} else {
    echo "No yarn type found with name 'Feb-60' or it was already updated";
}

// Show current yarn types
echo "<br><br>Current yarn types:<br>";
$stmt = $pdo->prepare('SELECT * FROM yarn_types WHERE company_id = ? ORDER BY name');
$stmt->execute([$company['id']]);
$yarn_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($yarn_types as $yt) {
    echo "- " . htmlspecialchars($yt['name']) . "<br>";
}
?>
