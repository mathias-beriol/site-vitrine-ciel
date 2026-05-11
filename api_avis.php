<?php
// =============================================
//   API AVIS – BAC PRO CIEL
//   Connexion Railway MySQL
//   Création automatique de la table
// =============================================
 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
 
// Connexion Railway (variables d'environnement en priorité)
$host     = getenv("MYSQL_HOST")     ?: "mysql.railway.internal";
$port     = getenv("MYSQL_PORT")     ?: "3306";
$dbname   = getenv("MYSQL_DATABASE") ?: "railway";
$username = getenv("MYSQL_USER")     ?: "root";
$password = getenv("MYSQL_ROOT_PASSWORD") ?: "TVjodbFSikPAuJOLeaHnoVzKOzZtiCUn";
 
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
    // Créer la table automatiquement si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS avis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        statut VARCHAR(20) NOT NULL,
        annee VARCHAR(20) NOT NULL,
        note INT NOT NULL,
        commentaire TEXT NOT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
 
} catch (PDOException $e) {
    echo json_encode(["succes" => false, "message" => "Erreur de connexion : " . $e->getMessage()]);
    exit;
}
 
// Liste de mots interdits
$mots_interdits = [
    "merde", "connard", "connasse", "putain", "salope", "enculé",
    "enculer", "fdp", "ntm", "ta gueule", "con", "caca", "pipi",
    "shit", "fuck", "bastard", "hitler", "nazi", "adolf",
    "juif", "arabe", "raciste", "discrimination", "wesh",
    "wish", "pourri", "pourrie", "nul",
];
 
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
 
    if (empty($data["nom"]) || empty($data["statut"]) || empty($data["annee"]) || empty($data["note"]) || empty($data["commentaire"])) {
        echo json_encode(["succes" => false, "message" => "Tous les champs sont requis."]);
        exit;
    }
 
    $nom         = htmlspecialchars(trim($data["nom"]));
    $statut      = htmlspecialchars(trim($data["statut"]));
    $annee       = htmlspecialchars(trim($data["annee"]));
    $note        = intval($data["note"]);
    $commentaire = htmlspecialchars(trim($data["commentaire"]));
 
    if ($note < 1 || $note > 5) {
        echo json_encode(["succes" => false, "message" => "La note doit être entre 1 et 5."]);
        exit;
    }
 
    if (contientMotInterdit($nom, $mots_interdits)) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton nom contient des mots non autorisés."]);
        exit;
    }
 
    if (contientMotInterdit($commentaire, $mots_interdits)) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton commentaire contient des mots non autorisés. Merci de rester respectueux !"]);
        exit;
    }
 
    if (mb_strlen($commentaire) < 10) {
        echo json_encode(["succes" => false, "message" => "⚠️ Ton commentaire est trop court (minimum 10 caractères)."]);
        exit;
    }
 
    $stmt = $pdo->prepare("INSERT INTO avis (nom, statut, annee, note, commentaire) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $statut, $annee, $note, $commentaire]);
 
    echo json_encode(["succes" => true, "message" => "Avis publié avec succès !"]);
    exit;
}
 
echo json_encode(["succes" => false, "message" => "Méthode non autorisée."]);
?>
