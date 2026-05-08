<?php
// =============================================
//   API AVIS – BAC PRO CIEL
//   Avec filtre automatique de mots interdits
// =============================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Connexion BDD
$host     = "localhost";
$dbname   = "site_vitrine";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["succes" => false, "message" => "Erreur de connexion."]);
    exit;
}

// =============================================
//   LISTE DE MOTS INTERDITS
//   Ajoute autant de mots que tu veux !
// =============================================
$mots_interdits = [
    // Insultes
    "merde", "connard", "connasse", "putain", "salope", "enculé",
    "enculer", "fdp", "ntm", "ta gueule", "va te faire", "con",
    "caca", "pipi", "shit", "fuck", "bastard",
    // Noms problématiques
    "hitler", "nazi", "adolf",
    // Contenu inapproprié
    "juif", "arabe", "noir", "blanc", "raciste", "discrimination",
    "wesh", "jsp", "lol", "mdr",
    // Spam
    "wish", "site de merde", "nul", "pourri", "pourrie",
];

// Fonction de vérification
function contientMotInterdit($texte, $mots_interdits) {
    $texte_lower = mb_strtolower($texte, 'UTF-8');
    foreach ($mots_interdits as $mot) {
        if (mb_strpos($texte_lower, mb_strtolower($mot, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

$methode = $_SERVER["REQUEST_METHOD"];

// GET : Récupérer tous les avis
if ($methode === "GET") {
    $stmt = $pdo->query("SELECT * FROM avis ORDER BY date_creation DESC");
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["succes" => true, "avis" => $avis]);
    exit;
}

// POST : Ajouter un avis
if ($methode === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    // Vérification champs requis
    if (
        empty($data["nom"]) ||
        empty($data["statut"]) ||
        empty($data["annee"]) ||
        empty($data["note"]) ||
        empty($data["commentaire"])
    ) {
        echo json_encode(["succes" => false, "message" => "Tous les champs sont requis."]);
        exit;
    }

    $nom         = htmlspecialchars(trim($data["nom"]));
    $statut      = htmlspecialchars(trim($data["statut"]));
    $annee       = htmlspecialchars(trim($data["annee"]));
    $note        = intval($data["note"]);
    $commentaire = htmlspecialchars(trim($data["commentaire"]));

    // Validation note
    if ($note < 1 || $note > 5) {
        echo json_encode(["succes" => false, "message" => "La note doit être entre 1 et 5."]);
        exit;
    }

    // Vérification mots interdits dans NOM
    if (contientMotInterdit($nom, $mots_interdits)) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton nom contient des mots non autorisés."]);
        exit;
    }

    // Vérification mots interdits dans COMMENTAIRE
    if (contientMotInterdit($commentaire, $mots_interdits)) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton commentaire contient des mots non autorisés. Merci de rester respectueux !"]);
        exit;
    }

    // Longueur minimale du commentaire
    if (mb_strlen($commentaire) < 10) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton commentaire est trop court (minimum 10 caractères)."]);
        exit;
    }

    // Insertion
    $stmt = $pdo->prepare("INSERT INTO avis (nom, statut, annee, note, commentaire) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $statut, $annee, $note, $commentaire]);

    echo json_encode(["succes" => true, "message" => "Avis publié avec succès !"]);
    exit;
}

echo json_encode(["succes" => false, "message" => "Méthode non autorisée."]);
?>
