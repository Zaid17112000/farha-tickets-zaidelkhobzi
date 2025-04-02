<?php
    require_once "../config/connectDB.php";

    $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
        FROM evenement
        LEFT JOIN edition ON evenement.eventId = edition.eventId
        ORDER BY edition.dateEvent ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /** --------- */

    $query = "SELECT DISTINCT eventType FROM evenement";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $eventType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /** Search by title */

    if(isset($_POST["search"])) {
        $searchByTitle = "%" . trim($_POST["query"]) . "%";

        $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
        FROM evenement
        LEFT JOIN edition ON evenement.eventId = edition.eventId
        WHERE evenement.eventTitle LIKE :searchByTitle
        ORDER BY edition.dateEvent ASC";
    
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":searchByTitle", $searchByTitle);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Filter par titre */

    if(isset($_POST["appliquer"])) {
        $categorie = isset($_POST["category"]) ? trim($_POST["category"]) : "";
        $f_date = trim($_POST["first-date"]);
        $s_date = trim($_POST["second-date"]);
    
        if (!empty($categorie) && !empty($f_date) && !empty($s_date)) {
            // Filter by both category AND date range
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE evenement.eventType = :eventType
                AND edition.dateEvent BETWEEN :first_date AND :last_date
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":eventType", $categorie);
            $stmt->bindParam(":first_date", $f_date);
            $stmt->bindParam(":last_date", $s_date);
        } 
        elseif (!empty($categorie)) {
            // Filter only by category
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE evenement.eventType = :eventType
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":eventType", $categorie);
        } 
        elseif (!empty($f_date) && !empty($s_date)) {
            // Filter only by date range
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE edition.dateEvent BETWEEN :first_date AND :last_date
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":first_date", $f_date);
            $stmt->bindParam(":last_date", $s_date);
        } 
        else {
            // No filter, fetch all events
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
        }
    
        // Execute the query
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }    

    if (isset($_POST["nettoyer"])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }    
?>

<?php include "../../views/accueil.html" ?>