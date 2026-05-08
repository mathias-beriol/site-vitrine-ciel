// =============================================
//   JEU DE MORPION – BAC PRO CIEL
//   Portail d'accès : battre le bot = entrer
//   Bot niveau : NORMAL (heuristique)
// =============================================

let plateau = ["", "", "", "", "", "", "", "", ""];
let joueurActuel = "X";
let partieTerminee = false;
let scoreJoueur = 0;
let scoreBot    = 0;
let scoreNul    = 0;

const COMBOS_GAGNANTES = [
    [0, 1, 2], [3, 4, 5], [6, 7, 8],
    [0, 3, 6], [1, 4, 7], [2, 5, 8],
    [0, 4, 8], [2, 4, 6],
];

const COINS  = [0, 2, 6, 8];
const BORDS  = [1, 3, 5, 7];
const CENTRE = 4;

document.addEventListener("DOMContentLoaded", initialiserJeu);

function initialiserJeu() {
    plateau        = ["", "", "", "", "", "", "", "", ""];
    joueurActuel   = "X";
    partieTerminee = false;
    cacherMsgRefuse();
    creerGrille();
    majStatut("À toi de jouer ! Tu es les ✕");
}

function rejouer() { initialiserJeu(); }

function creerGrille() {
    const grille = document.getElementById("grille");
    grille.innerHTML = "";
    for (let i = 0; i < 9; i++) {
        const caseDiv = document.createElement("div");
        caseDiv.classList.add("case");
        caseDiv.dataset.index = i;
        caseDiv.addEventListener("click", () => clicJoueur(i));
        grille.appendChild(caseDiv);
    }
}

function clicJoueur(index) {
    if (plateau[index] !== "" || partieTerminee || joueurActuel !== "X") return;
    jouerCoup("X", index);
    if (partieTerminee) return;
    joueurActuel = "O";
    majStatut("🤖 Le bot réfléchit…");
    setTimeout(tourBot, 500);
}

// =============================================
//   LOGIQUE DU BOT
// =============================================
function tourBot() {
    if (partieTerminee) return;

    let choix = null;

    // 1. Gagner si possible
    choix = trouverCoupGagnant("O");
    if (choix !== null) { jouerCoup("O", choix); finTourBot(); return; }

    // 2. Bloquer le joueur
    choix = trouverCoupGagnant("X");
    if (choix !== null) { jouerCoup("O", choix); finTourBot(); return; }

    // 3. Centre
    if (plateau[CENTRE] === "") { jouerCoup("O", CENTRE); finTourBot(); return; }

    // 4. Coin opposé au joueur
    choix = trouverCoinOppose();
    if (choix !== null) { jouerCoup("O", choix); finTourBot(); return; }

    // 5. N'importe quel coin
    choix = COINS.find(i => plateau[i] === "") ?? null;
    if (choix !== null) { jouerCoup("O", choix); finTourBot(); return; }

    // 6. N'importe quel bord
    choix = BORDS.find(i => plateau[i] === "") ?? null;
    if (choix !== null) { jouerCoup("O", choix); finTourBot(); return; }
}

function finTourBot() {
    if (!partieTerminee) {
        joueurActuel = "X";
        majStatut("À toi de jouer ! Tu es les ✕");
    }
}

function trouverCoupGagnant(symbole) {
    for (const [a, b, c] of COMBOS_GAGNANTES) {
        const ligne = [plateau[a], plateau[b], plateau[c]];
        const nbSymbole = ligne.filter(v => v === symbole).length;
        const nbVide    = ligne.filter(v => v === "").length;
        if (nbSymbole === 2 && nbVide === 1) {
            return [a, b, c][ligne.indexOf("")];
        }
    }
    return null;
}

function trouverCoinOppose() {
    const opposes = { 0: 8, 2: 6, 6: 2, 8: 0 };
    for (const [coin, oppose] of Object.entries(opposes)) {
        if (plateau[coin] === "X" && plateau[oppose] === "") {
            return parseInt(oppose);
        }
    }
    return null;
}

// =============================================
//   GESTION DES RÉSULTATS
// =============================================
function jouerCoup(symbole, index) {
    plateau[index] = symbole;
    afficherCase(index, symbole);

    const combo = trouverCombo(symbole);
    if (combo) {
        surlgnerCasesGagnantes(combo);
        partieTerminee = true;

        if (symbole === "X") {
            // ✅ VICTOIRE DU JOUEUR → accès débloqué !
            majStatut("🎉 Bravo, tu as gagné !");
            scoreJoueur++;
            majScore();
            // Afficher l'overlay après un court délai
            setTimeout(() => {
                document.getElementById("overlay-victoire").classList.add("open");
            }, 800);

        } else {
            // ❌ Bot gagne → accès refusé
            majStatut("😈 Le bot a gagné ! Rejoue pour entrer.");
            scoreBot++;
            majScore();
            afficherMsgRefuse();
        }
        return;
    }

    if (plateau.every(c => c !== "")) {
        // Match nul → accès refusé
        majStatut("🤝 Match nul ! Rejoue pour entrer.");
        partieTerminee = true;
        scoreNul++;
        majScore();
        afficherMsgRefuse();
    }
}

function afficherCase(index, symbole) {
    const cases = document.querySelectorAll(".case");
    const caseDiv = cases[index];
    caseDiv.textContent = symbole === "X" ? "✕" : "○";
    caseDiv.classList.add(symbole === "X" ? "joueur" : "bot", "anime");
}

function trouverCombo(symbole) {
    return COMBOS_GAGNANTES.find(
        ([a, b, c]) => plateau[a] === symbole && plateau[b] === symbole && plateau[c] === symbole
    ) || null;
}

function surlgnerCasesGagnantes(combo) {
    const cases = document.querySelectorAll(".case");
    combo.forEach(idx => cases[idx].classList.add("gagnant"));
}

function majStatut(message) {
    const el = document.getElementById("statut");
    if (el) el.textContent = message;
}

function majScore() {
    document.getElementById("score-joueur").textContent = scoreJoueur;
    document.getElementById("score-bot").textContent    = scoreBot;
    document.getElementById("score-nul").textContent    = scoreNul;
}

function afficherMsgRefuse() {
    const el = document.getElementById("msg-refuse");
    if (el) {
        el.style.display = "block";
        // Relancer l'animation shake
        el.style.animation = "none";
        el.offsetHeight;
        el.style.animation = "shake 0.4s ease";
    }
}

function cacherMsgRefuse() {
    const el = document.getElementById("msg-refuse");
    if (el) el.style.display = "none";
}
