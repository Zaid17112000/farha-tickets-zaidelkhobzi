<?php
    session_start();
    require_once("../config/connectDB.php");

    if (!isset($_SESSION["user"]) || !isset($_GET["eventId"])) {
        echo "<p>Aucun billet trouvé, utilisateur non connecté, ou événement non spécifié.</p>";
        exit;
    }

    $eventId = $_GET["eventId"];
    $userId = $_SESSION["user"]["user_id"];

    // Fetch ticket data from the database
    $query = "
        SELECT 
            ev.eventTitle,
            ed.dateEvent,
            ed.timeEvent,
            ev.TariffNormal AS ticketPriceNormal,
            ev.TariffReduit AS ticketPriceReduit,
            r.qteBilletsNormal AS quantityNormal,
            r.qteBilletsReduit AS quantityReduit,
            (r.qteBilletsNormal * ev.TariffNormal) AS totalNormal,
            (r.qteBilletsReduit * ev.TariffReduit) AS totalReduit
        FROM 
            reservation r
        JOIN 
            edition ed ON r.editionId = ed.editionId
        JOIN 
            evenement ev ON ed.eventId = ev.eventId
        WHERE 
            r.idUser = :idUser 
            AND ed.eventId = :eventId
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":idUser", $userId);
    $stmt->bindParam(":eventId", $eventId);
    $stmt->execute();
    $facture = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($facture)) {
        echo "<p>Aucun billet trouvé pour cet événement.</p>";
        exit;
    }

    // Fetch user details
    $query = "SELECT nomUser, mailUser FROM utilisateur WHERE idUser = :idUser";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":idUser", $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare ticket data for display
    $tickets = [];
    foreach ($facture as $row) {
        if ($row["quantityNormal"] > 0) {
            $tickets[] = [
                "ticketType" => "Normal",
                "ticketPrice" => $row["ticketPriceNormal"],
                "quantity" => $row["quantityNormal"],
                "total" => $row["totalNormal"]
            ];
        }
        if ($row["quantityReduit"] > 0) {
            $tickets[] = [
                "ticketType" => "Réduit",
                "ticketPrice" => $row["ticketPriceReduit"],
                "quantity" => $row["quantityReduit"],
                "total" => $row["totalReduit"]
            ];
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture</title>
    <link rel="stylesheet" href="../../css/facture.css">
    <!-- Include html2pdf.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>

</head>
<body>
    <header>
        <a href="./accueil.php" style="text-decoration: none;"><h1 class="logo">farhaEvents</h1></a>
        <nav>
            <ul>
                <a href="./accueil.php"><li>Accueil</li></a>
                <a href="./profil.php"><li>Mon profile</li></a>
                <a href="./login.php"><li>Se connecter</li></a>
            </ul>
        </nav>
    </header>
    <div class="invoice">
        <div class="header">
            <div>
                <p class="title">ASSOCIATION FARHA</p>
                <p>Centre Culturel Farha, Tanger</p>
            </div>
            <div>
                <p><strong>Client :</strong><br> <?= htmlspecialchars($user["nomUser"]) ?></p>
                <p><strong>Adresse email :</strong><br> <?= htmlspecialchars($user["mailUser"]) ?></p>
            </div>
        </div>
        <p><strong><?= htmlspecialchars($facture[0]["eventTitle"]) ?></strong><br><?= htmlspecialchars($facture[0]["dateEvent"]) ?> à <?= htmlspecialchars($facture[0]["timeEvent"]) ?></p>
        <h2>FACTURE #76528</h2>
        <table class="table">
            <tr>
                <th>Tarif</th>
                <th>Prix</th>
                <th>Qte</th>
                <th>Total</th>
            </tr>
            <?php 
            $grandTotal = 0;
            foreach ($tickets as $ticket): 
                $grandTotal += $ticket["total"];
            ?>
                <tr>
                    <td><?= htmlspecialchars($ticket["ticketType"]) ?></td>
                    <td><?= htmlspecialchars($ticket["ticketPrice"]) ?></td>
                    <td><?= htmlspecialchars($ticket["quantity"]) ?></td>
                    <td><?= htmlspecialchars($ticket["total"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="total">Total à payer : <strong><?= number_format($grandTotal, 2) ?> MAD</strong></p>
        <p class="footer">MERCI !</p>

        <button id="downloadBtn">Download Facture as PDF</button>

    </div>
    
    <script>
        document.getElementById('downloadBtn').addEventListener('click', function() {
            // Select the content to be converted to PDF
            var element = document.querySelector('.invoice');
            
            // Use html2pdf to generate the PDF from the content
            var options = {
                margin:       10,
                filename:     'facture.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { dpi: 192, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().from(element).set(options).save();
        });
    </script>

</body>
</html>