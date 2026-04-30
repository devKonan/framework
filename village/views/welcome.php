<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($appName) ?> — Framework PHP pour l'Afrique</title>
<style>
:root {
  --orange:   #ff6600;
  --orange2:  #ff8533;
  --dark:     #0b0b0b;
  --dark2:    #111111;
  --dark3:    #1a1a1a;
  --dark4:    #222222;
  --border:   #2a2a2a;
  --text:     #e8e8e8;
  --muted:    #888888;
  --green:    #22c55e;
  --blue:     #38bdf8;
  --purple:   #a78bfa;
  --radius:   12px;
  --font:     -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
  --mono:     'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  background: var(--dark);
  color: var(--text);
  font-family: var(--font);
  line-height: 1.6;
  overflow-x: hidden;
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--dark2); }
::-webkit-scrollbar-thumb { background: var(--orange); border-radius: 3px; }

/* ── Navbar ── */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 2rem; height: 64px;
  background: rgba(11,11,11,.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
}

.nav-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
.nav-brand svg { width: 36px; height: 36px; flex-shrink: 0; }
.nav-name { font-size: 1.25rem; font-weight: 700; color: var(--text); letter-spacing: -.5px; }
.nav-name span { color: var(--orange); }
.nav-badge {
  font-size: .65rem; font-weight: 700; letter-spacing: .05em;
  background: linear-gradient(135deg, var(--orange), var(--orange2));
  color: #fff; padding: 2px 8px; border-radius: 99px;
  text-transform: uppercase;
}

.nav-links { display: flex; align-items: center; gap: 1.5rem; }
.nav-links a {
  color: var(--muted); text-decoration: none; font-size: .9rem;
  transition: color .2s;
}
.nav-links a:hover { color: var(--orange); }
.nav-gh {
  display: flex; align-items: center; gap: .4rem;
  padding: .4rem .9rem; border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--dark3);
  color: var(--text) !important;
  font-size: .85rem; font-weight: 500;
  transition: border-color .2s, background .2s !important;
}
.nav-gh:hover { border-color: var(--orange) !important; background: var(--dark4) !important; }

/* ── Hero ── */
.hero {
  min-height: 100vh;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  text-align: center;
  padding: 6rem 2rem 4rem;
  position: relative;
  overflow: hidden;
}

.hero-glow {
  position: absolute; top: 20%; left: 50%; transform: translateX(-50%);
  width: 600px; height: 600px; border-radius: 50%;
  background: radial-gradient(circle, rgba(255,102,0,.12) 0%, transparent 70%);
  pointer-events: none;
}

.hero-tag {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .4rem 1rem; border-radius: 99px;
  border: 1px solid rgba(255,102,0,.3);
  background: rgba(255,102,0,.08);
  color: var(--orange2); font-size: .8rem; font-weight: 600;
  letter-spacing: .05em; text-transform: uppercase;
  margin-bottom: 2rem;
  animation: fadeUp .6s ease both;
}
.hero-tag .dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--orange);
  animation: pulse 2s infinite;
}

.hero h1 {
  font-size: clamp(2.5rem, 7vw, 5rem);
  font-weight: 800; letter-spacing: -2px; line-height: 1.1;
  margin-bottom: 1.5rem;
  animation: fadeUp .6s .1s ease both;
}
.hero h1 .accent { color: var(--orange); }
.hero h1 .gradient {
  background: linear-gradient(135deg, var(--orange), #ffaa00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero p {
  max-width: 560px; font-size: 1.15rem; color: var(--muted);
  margin-bottom: 2.5rem;
  animation: fadeUp .6s .2s ease both;
}

.hero-actions {
  display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;
  animation: fadeUp .6s .3s ease both;
}

.btn-primary {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .8rem 1.8rem; border-radius: 10px;
  background: linear-gradient(135deg, var(--orange), var(--orange2));
  color: #fff; font-weight: 700; font-size: .95rem;
  text-decoration: none; border: none; cursor: pointer;
  box-shadow: 0 4px 24px rgba(255,102,0,.35);
  transition: transform .2s, box-shadow .2s;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(255,102,0,.45); }

.btn-secondary {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .8rem 1.8rem; border-radius: 10px;
  border: 1px solid var(--border); background: var(--dark3);
  color: var(--text); font-weight: 600; font-size: .95rem;
  text-decoration: none;
  transition: border-color .2s, background .2s;
}
.btn-secondary:hover { border-color: var(--orange); background: var(--dark4); }

.hero-cmd {
  margin-top: 3rem;
  display: inline-flex; align-items: center; gap: .75rem;
  padding: .8rem 1.4rem; border-radius: 10px;
  border: 1px solid var(--border); background: var(--dark2);
  font-family: var(--mono); font-size: .9rem; color: var(--green);
  animation: fadeUp .6s .4s ease both;
}
.hero-cmd .prompt { color: var(--orange); user-select: none; }
.hero-cmd .copy-btn {
  background: none; border: none; cursor: pointer; color: var(--muted);
  display: flex; align-items: center; padding: 0;
  transition: color .2s;
}
.hero-cmd .copy-btn:hover { color: var(--orange); }

/* ── Section wrapper ── */
section { padding: 5rem 2rem; max-width: 1100px; margin: 0 auto; }
.section-label {
  font-size: .75rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--orange);
  margin-bottom: .75rem;
}
.section-title {
  font-size: clamp(1.6rem, 3.5vw, 2.5rem);
  font-weight: 800; letter-spacing: -.5px; margin-bottom: 1rem;
}
.section-sub { color: var(--muted); max-width: 540px; margin-bottom: 3rem; }

/* ── Quick start ── */
.quickstart { background: var(--dark2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.quickstart > div { max-width: 1100px; margin: 0 auto; padding: 5rem 2rem; }

.steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
.step {
  background: var(--dark3); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 1.5rem;
  display: flex; flex-direction: column; gap: .75rem;
  transition: border-color .2s, transform .2s;
}
.step:hover { border-color: rgba(255,102,0,.4); transform: translateY(-3px); }
.step-num {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, var(--orange), var(--orange2));
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem; color: #fff; flex-shrink: 0;
}
.step-title { font-weight: 700; font-size: .95rem; }
.step-desc { color: var(--muted); font-size: .85rem; }
.step-code {
  font-family: var(--mono); font-size: .8rem;
  background: var(--dark); border: 1px solid var(--border);
  border-radius: 8px; padding: .75rem 1rem; color: var(--green);
  white-space: pre-line;
}

/* ── Features grid ── */
.features { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.feat {
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 1.5rem;
  transition: border-color .25s, transform .25s;
}
.feat:hover { border-color: rgba(255,102,0,.45); transform: translateY(-4px); }
.feat-icon {
  width: 44px; height: 44px; border-radius: 10px;
  background: rgba(255,102,0,.12); border: 1px solid rgba(255,102,0,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; margin-bottom: 1rem;
}
.feat h3 { font-size: .95rem; font-weight: 700; margin-bottom: .4rem; }
.feat p { color: var(--muted); font-size: .82rem; line-height: 1.5; }
.feat-tags { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .75rem; }
.tag {
  font-size: .7rem; font-weight: 600; padding: 2px 8px; border-radius: 99px;
  background: rgba(255,102,0,.1); color: var(--orange2); border: 1px solid rgba(255,102,0,.2);
}

/* ── Code examples ── */
.code-block {
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
}
.code-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .7rem 1rem; background: var(--dark3); border-bottom: 1px solid var(--border);
  font-size: .8rem; color: var(--muted); font-family: var(--mono);
}
.code-dots { display: flex; gap: 6px; }
.code-dots span {
  width: 12px; height: 12px; border-radius: 50%;
}
.code-dots span:nth-child(1) { background: #ff5f57; }
.code-dots span:nth-child(2) { background: #febc2e; }
.code-dots span:nth-child(3) { background: #28c840; }
pre {
  padding: 1.5rem; overflow-x: auto; font-family: var(--mono);
  font-size: .85rem; line-height: 1.7;
}
.kw   { color: #c792ea; }  /* keywords */
.fn   { color: #82aaff; }  /* functions */
.str  { color: #c3e88d; }  /* strings */
.cmt  { color: #546e7a; font-style: italic; }
.var  { color: #f78c6c; }  /* variables */
.cls  { color: var(--orange2); }  /* classes */
.op   { color: #89ddff; }  /* operators / punctuation */
.num  { color: #f78c6c; }  /* numbers */

.examples-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
@media(max-width: 768px) { .examples-grid { grid-template-columns: 1fr; } }

/* ── CLI Commands ── */
.cli-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.cli-table th {
  text-align: left; padding: .75rem 1rem;
  background: var(--dark3); border-bottom: 2px solid var(--border);
  color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .06em;
}
.cli-table td {
  padding: .75rem 1rem; border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.cli-table tr:last-child td { border-bottom: none; }
.cli-table tr:hover td { background: rgba(255,255,255,.02); }
.cmd-cell { font-family: var(--mono); font-size: .82rem; color: var(--green); white-space: nowrap; }
.cmd-category {
  display: inline-block; font-size: .68rem; font-weight: 700;
  padding: 2px 7px; border-radius: 99px; margin-right: .5rem; letter-spacing: .04em;
}
.cat-http    { background: rgba(56,189,248,.12); color: var(--blue); border: 1px solid rgba(56,189,248,.25); }
.cat-db      { background: rgba(34,197,94,.12);  color: var(--green); border: 1px solid rgba(34,197,94,.25); }
.cat-mail    { background: rgba(167,139,250,.12);color: var(--purple); border: 1px solid rgba(167,139,250,.25); }
.cat-sms     { background: rgba(255,102,0,.12);  color: var(--orange2); border: 1px solid rgba(255,102,0,.25); }
.cat-logs    { background: rgba(254,188,46,.12); color: #febc2e; border: 1px solid rgba(254,188,46,.25); }
.cat-gen     { background: rgba(255,255,255,.05);color: var(--muted); border: 1px solid var(--border); }

/* ── Drivers section ── */
.drivers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.driver-card {
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 1.25rem;
  transition: border-color .2s;
}
.driver-card:hover { border-color: rgba(255,102,0,.4); }
.driver-type {
  font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
  color: var(--muted); margin-bottom: .5rem;
}
.driver-name { font-weight: 700; font-size: .95rem; margin-bottom: .4rem; }
.driver-desc { font-size: .8rem; color: var(--muted); }

/* ── Creator ── */
.creator-section {
  background: linear-gradient(135deg, rgba(255,102,0,.06), rgba(255,102,0,.02));
  border: 1px solid rgba(255,102,0,.15);
  border-radius: 20px; padding: 3rem 2rem; text-align: center; margin: 0 2rem;
}
.creator-avatar {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg, var(--orange), #ffaa00);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 800; color: #fff;
  margin: 0 auto 1.25rem;
  box-shadow: 0 0 0 4px rgba(255,102,0,.2), 0 0 0 8px rgba(255,102,0,.08);
}
.creator-name { font-size: 1.5rem; font-weight: 800; margin-bottom: .25rem; }
.creator-handle { color: var(--orange); font-size: .9rem; font-family: var(--mono); margin-bottom: .75rem; }
.creator-bio { color: var(--muted); max-width: 440px; margin: 0 auto 1.5rem; font-size: .9rem; }
.creator-links { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.creator-link {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .5rem 1.1rem; border-radius: 8px; border: 1px solid var(--border);
  background: var(--dark3); color: var(--text); text-decoration: none;
  font-size: .85rem; font-weight: 500;
  transition: border-color .2s, color .2s;
}
.creator-link:hover { border-color: var(--orange); color: var(--orange); }

/* ── Footer ── */
footer {
  border-top: 1px solid var(--border); padding: 2.5rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: .75rem;
  text-align: center;
}
footer p { color: var(--muted); font-size: .85rem; }
footer .version { font-family: var(--mono); color: var(--orange); font-size: .8rem; }
.ci-flag {
  font-size: 1.2rem;
}

/* ── Env banner ── */
.env-banner {
  background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2);
  border-radius: 10px; padding: 1rem 1.5rem;
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 3rem; font-size: .88rem;
}
.env-banner .env-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); flex-shrink: 0; animation: pulse 2s infinite; }
.env-banner .env-key { font-family: var(--mono); color: var(--green); font-size: .82rem; }

/* ── Animations ── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: .6; transform: scale(.85); }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-in {
  opacity: 0; transform: translateY(16px);
  transition: opacity .5s ease, transform .5s ease;
}
.fade-in.visible { opacity: 1; transform: translateY(0); }

/* ── Responsive ── */
@media(max-width: 600px) {
  .nav-links { display: none; }
  .hero h1 { font-size: 2.4rem; }
  .creator-section { margin: 0 .5rem; }
  .steps { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav>
  <a href="#" class="nav-brand">
    <!-- Logo SVG -->
    <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect width="36" height="36" rx="9" fill="url(#bg)"/>
      <path d="M10 8h9.5c3.5 0 5.8 1.8 5.8 4.8 0 1.8-.9 3.2-2.4 4 2 .7 3.2 2.3 3.2 4.4 0 3.4-2.5 5.4-6.6 5.4H10V8z" fill="white"/>
      <path d="M14 12v4h5c1.4 0 2.2-.8 2.2-2s-.8-2-2.2-2h-5zm0 7.5v4.5h5.5c1.6 0 2.5-.9 2.5-2.25S21.1 19.5 19.5 19.5H14z" fill="url(#accent)"/>
      <!-- Flame dots -->
      <circle cx="28" cy="8" r="2.5" fill="url(#flame1)"/>
      <circle cx="28" cy="14" r="1.5" fill="url(#flame2)"/>
      <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#1a1a1a"/>
          <stop offset="100%" stop-color="#111111"/>
        </linearGradient>
        <linearGradient id="accent" x1="14" y1="12" x2="26" y2="27" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#ff6600"/>
          <stop offset="100%" stop-color="#ff8533"/>
        </linearGradient>
        <linearGradient id="flame1" x1="25.5" y1="5.5" x2="30.5" y2="10.5" gradientUnits="userSpaceOnUse">
          <stop stop-color="#ff6600"/>
          <stop offset="1" stop-color="#ffaa00"/>
        </linearGradient>
        <linearGradient id="flame2" x1="26.5" y1="12.5" x2="29.5" y2="15.5" gradientUnits="userSpaceOnUse">
          <stop stop-color="#ff8533"/>
          <stop offset="1" stop-color="#ffc200"/>
        </linearGradient>
      </defs>
    </svg>
    <span class="nav-name">Briko<span>code</span></span>
    <span class="nav-badge">v<?= htmlspecialchars($version) ?></span>
  </a>
  <div class="nav-links">
    <a href="#demarrage">Démarrage</a>
    <a href="#fonctionnalites">Fonctionnalités</a>
    <a href="#exemples">Exemples</a>
    <a href="#commandes">CLI</a>
  </div>
</nav>

<!-- Hero -->
<div class="hero">
  <div class="hero-glow"></div>
  <div class="hero-tag">
    <span class="dot"></span>
    Framework PHP Africain · Version <?= htmlspecialchars($version) ?>
  </div>
  <h1>Le framework PHP<br><span class="gradient">fait pour l'Afrique</span></h1>
  <p>Léger, rapide, sans dépendances. Conçu pour les connexions instables, le SMS natif, et les développeurs africains qui construisent pour demain.</p>
  <div class="hero-actions">
    <a href="#demarrage" class="btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      Démarrer
    </a>
  </div>
  <div class="hero-cmd">
    <span class="prompt">$</span>
    <span>composer create-project devkonan/brikocode mon-app</span>
    <button class="copy-btn" onclick="navigator.clipboard.writeText('composer create-project devkonan/brikocode mon-app')" title="Copier">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
    </button>
  </div>
</div>

<!-- Quick Start -->
<div class="quickstart" id="demarrage">
  <div>
    <p class="section-label">Installation</p>
    <h2 class="section-title">Démarrage en 3 étapes</h2>
    <p class="section-sub">De zéro à une API fonctionnelle en moins de 2 minutes.</p>

    <div class="steps">
      <div class="step fade-in">
        <div class="step-num">1</div>
        <div class="step-title">Installer via Composer</div>
        <div class="step-desc">Crée un nouveau projet Brikocode dans le dossier de ton choix.</div>
        <div class="step-code">composer create-project devkonan/brikocode mon-app
cd mon-app</div>
      </div>
      <div class="step fade-in">
        <div class="step-num">2</div>
        <div class="step-title">Configurer l'environnement</div>
        <div class="step-desc">Génère ton fichier <code style="color:var(--orange)">.env</code> et adapte tes variables (DB, Mail, SMS...).</div>
        <div class="step-code">php briko env:setup
# Ouvre .env et remplis tes valeurs</div>
      </div>
      <div class="step fade-in">
        <div class="step-num">3</div>
        <div class="step-title">Lancer le serveur</div>
        <div class="step-desc">Démarre le serveur de développement intégré sur le port 8000.</div>
        <div class="step-code">php briko feu
# → http://localhost:8000</div>
      </div>
    </div>

    <div class="env-banner fade-in" style="margin-top:2.5rem">
      <span class="env-dot"></span>
      <div>
        <strong>Environnement actif :</strong>
        <span class="env-key"> <?= htmlspecialchars($appEnv) ?></span> &nbsp;|&nbsp;
        <span class="env-key">APP_NAME=<?= htmlspecialchars($appName) ?></span> &nbsp;|&nbsp;
        <span class="env-key">APP_URL=<?= htmlspecialchars($appUrl) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Features -->
<section id="fonctionnalites">
  <p class="section-label">Fonctionnalités</p>
  <h2 class="section-title">Tout ce qu'il te faut, rien de plus</h2>
  <p class="section-sub">Zéro dépendance externe. Chaque module est conçu pour fonctionner en Afrique : connexions lentes, SMS natif, offline-first.</p>

  <div class="features">
    <div class="feat fade-in">
      <div class="feat-icon">🛣️</div>
      <h3>Routage dynamique</h3>
      <p>Routes RESTful avec paramètres <code style="color:var(--orange)">{id}</code>, middlewares chaînés, méthodes GET/POST/PUT/PATCH/DELETE.</p>
      <div class="feat-tags"><span class="tag">itinéraire</span><span class="tag">REST</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">🗄️</div>
      <h3>Query Builder fluent</h3>
      <p>ORM léger sur PDO natif. MySQL, PostgreSQL, SQLite. Pagination, relations, migrations versionnées.</p>
      <div class="feat-tags"><span class="tag">grenier</span><span class="tag">PDO</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">📲</div>
      <h3>SMS natif</h3>
      <p>Africa's Talking, Twilio, HTTP générique, OTP avec vérification. Priorité Africa's Talking pour la couverture CI/GH/SN.</p>
      <div class="feat-tags"><span class="tag">tamtam</span><span class="tag">OTP</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">✉️</div>
      <h3>Mail natif</h3>
      <p>SMTP via sockets PHP (TLS/SSL/STARTTLS), SendGrid, Mailgun. Templates PHP, pièces jointes, Mailable.</p>
      <div class="feat-tags"><span class="tag">courrier</span><span class="tag">SMTP</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">📶</div>
      <h3>Low Bandwidth</h3>
      <p>Mode économique automatique : gzip, sélection de champs, suppression des nulls. Idéal pour 2G/3G.</p>
      <div class="feat-tags"><span class="tag">gbaka</span><span class="tag">gzip</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">🔌</div>
      <h3>Offline-First</h3>
      <p>Cache de réponses GET et file d'attente JSON pour les requêtes d'écriture quand la DB est hors-ligne.</p>
      <div class="feat-tags"><span class="tag">sync</span><span class="tag">queue</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">📋</div>
      <h3>Logging structuré</h3>
      <p>Logs JSON par canal et par niveau. request_id, elapsed_ms, memory_kb. Tail en temps réel via CLI.</p>
      <div class="feat-tags"><span class="tag">djassa</span><span class="tag">JSON</span></div>
    </div>
    <div class="feat fade-in">
      <div class="feat-icon">⚡</div>
      <h3>CLI puissant</h3>
      <p>Générateurs, migrations, sync offline, test SMS/Mail, tail logs — tout depuis le terminal.</p>
      <div class="feat-tags"><span class="tag">djassa</span><span class="tag">CLI</span></div>
    </div>
  </div>
</section>

<!-- Code examples -->
<div style="background:var(--dark2); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
  <section id="exemples">
    <p class="section-label">Exemples</p>
    <h2 class="section-title">Simple à écrire, puissant à l'exécution</h2>
    <p class="section-sub">La syntaxe est fluide et expressive. Voici ce que tu peux faire dès la première heure.</p>

    <div class="examples-grid">

      <div class="code-block fade-in">
        <div class="code-header">
          <div class="code-dots"><span></span><span></span><span></span></div>
          village/routes.php
        </div>
        <pre><span class="cmt">// Routes dynamiques</span>
<span class="var">$router</span><span class="op">-></span><span class="fn">get</span><span class="op">(</span><span class="str">'/users/{id}'</span><span class="op">,</span>
    <span class="op">[</span><span class="cls">UserController</span><span class="op">::</span><span class="kw">class</span><span class="op">,</span> <span class="str">'show'</span><span class="op">]);</span>

<span class="var">$router</span><span class="op">-></span><span class="fn">post</span><span class="op">(</span><span class="str">'/users'</span><span class="op">,</span>
    <span class="op">[</span><span class="cls">UserController</span><span class="op">::</span><span class="kw">class</span><span class="op">,</span> <span class="str">'store'</span><span class="op">],</span>
    <span class="op">[</span><span class="cls">Guard</span><span class="op">::</span><span class="kw">class</span><span class="op">]);</span>

<span class="cmt">// Closure inline</span>
<span class="var">$router</span><span class="op">-></span><span class="fn">get</span><span class="op">(</span><span class="str">'/ping'</span><span class="op">,</span>
    <span class="kw">fn</span><span class="op">() =></span> <span class="op">[</span><span class="str">'pong'</span> <span class="op">=></span> <span class="kw">true</span><span class="op">]);</span></pre>
      </div>

      <div class="code-block fade-in">
        <div class="code-header">
          <div class="code-dots"><span></span><span></span><span></span></div>
          Query Builder — grenier
        </div>
        <pre><span class="cmt">// SELECT fluent</span>
<span class="var">$users</span> <span class="op">=</span> <span class="fn">db</span><span class="op">(</span><span class="str">'users'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">select</span><span class="op">(</span><span class="str">'id'</span><span class="op">,</span> <span class="str">'nom'</span><span class="op">,</span> <span class="str">'email'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">where</span><span class="op">(</span><span class="str">'actif'</span><span class="op">,</span> <span class="num">1</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">orderBy</span><span class="op">(</span><span class="str">'nom'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">paginate</span><span class="op">(</span><span class="num">20</span><span class="op">);</span>

<span class="cmt">// INSERT + GET ID</span>
<span class="var">$id</span> <span class="op">=</span> <span class="fn">db</span><span class="op">(</span><span class="str">'users'</span><span class="op">)-></span><span class="fn">insertGetId</span><span class="op">([</span>
    <span class="str">'nom'</span>   <span class="op">=></span> <span class="str">'Aya'</span><span class="op">,</span>
    <span class="str">'email'</span> <span class="op">=></span> <span class="str">'aya@ci.ci'</span><span class="op">,</span>
<span class="op">]);</span></pre>
      </div>

      <div class="code-block fade-in">
        <div class="code-header">
          <div class="code-dots"><span></span><span></span><span></span></div>
          SMS + OTP — tamtam
        </div>
        <pre><span class="cmt">// Envoyer un SMS</span>
<span class="fn">sms</span><span class="op">(</span><span class="str">'+225070000000'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">message</span><span class="op">(</span><span class="str">'Commande confirmée !'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">send</span><span class="op">();</span>

<span class="cmt">// Générer + envoyer un OTP</span>
<span class="var">$code</span> <span class="op">=</span> <span class="cls">SMS</span><span class="op">::</span><span class="fn">otp</span><span class="op">(</span><span class="str">'+225070000000'</span><span class="op">);</span>

<span class="cmt">// Vérifier</span>
<span class="kw">if</span> <span class="op">(</span><span class="cls">SMS</span><span class="op">::</span><span class="fn">verifyOtp</span><span class="op">(</span><span class="str">'+225070000000'</span><span class="op">,</span> <span class="var">$code</span><span class="op">)) {</span>
    <span class="cmt">// ✅ Numéro vérifié</span>
<span class="op">}</span></pre>
      </div>

      <div class="code-block fade-in">
        <div class="code-header">
          <div class="code-dots"><span></span><span></span><span></span></div>
          Mail — courrier
        </div>
        <pre><span class="cmt">// Email simple</span>
<span class="fn">mail_to</span><span class="op">(</span><span class="str">'client@ci.ci'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">subject</span><span class="op">(</span><span class="str">'Bienvenue !'</span><span class="op">)</span>
    <span class="op">-></span><span class="fn">view</span><span class="op">(</span><span class="str">'welcome'</span><span class="op">,</span> <span class="op">[</span><span class="str">'user'</span> <span class="op">=></span> <span class="var">$user</span><span class="op">])</span>
    <span class="op">-></span><span class="fn">send</span><span class="op">();</span>

<span class="cmt">// Mailable réutilisable</span>
<span class="cls">Mail</span><span class="op">::</span><span class="fn">send</span><span class="op">(</span><span class="kw">new</span> <span class="cls">WelcomeMail</span><span class="op">(</span><span class="var">$user</span><span class="op">));</span>

<span class="cmt">// Avec pièce jointe</span>
<span class="fn">mail_to</span><span class="op">(</span><span class="var">$email</span><span class="op">)-></span><span class="fn">attach</span><span class="op">(</span>
    <span class="fn">base_path</span><span class="op">(</span><span class="str">'storage/facture.pdf'</span><span class="op">))</span>
    <span class="op">-></span><span class="fn">send</span><span class="op">();</span></pre>
      </div>

    </div>
  </section>
</div>

<!-- Drivers -->
<section>
  <p class="section-label">Drivers</p>
  <h2 class="section-title">Adaptés au contexte africain</h2>
  <p class="section-sub">Tous les drivers sont interchangeables via une seule variable dans <code style="color:var(--orange)">.env</code>.</p>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;" class="drivers-wrap">
    <div>
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:1rem;">SMS — SMS_DRIVER</h3>
      <div class="drivers-grid">
        <div class="driver-card fade-in">
          <div class="driver-type">Recommandé Afrique</div>
          <div class="driver-name">Africa's Talking</div>
          <div class="driver-desc">Couverture CI, GH, SN, KE, NG et +10 pays.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">International</div>
          <div class="driver-name">Twilio</div>
          <div class="driver-desc">API mondiale, numéros locaux disponibles.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">Opérateurs locaux</div>
          <div class="driver-name">HTTP générique</div>
          <div class="driver-desc">Orange CI, MTN, Moov, Airtel — tout opérateur avec API REST.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">Développement</div>
          <div class="driver-name">log</div>
          <div class="driver-desc">SMS simulés dans les logs. Aucun envoi réel.</div>
        </div>
      </div>
    </div>
    <div>
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:1rem;">Mail — MAIL_DRIVER</h3>
      <div class="drivers-grid">
        <div class="driver-card fade-in">
          <div class="driver-type">Universel</div>
          <div class="driver-name">SMTP natif</div>
          <div class="driver-desc">Gmail, OVH, SES, Outlook. TLS/SSL/STARTTLS natif.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">Haute délivrabilité</div>
          <div class="driver-name">SendGrid</div>
          <div class="driver-desc">API HTTP, idéal pour volumes importants.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">Transactionnel</div>
          <div class="driver-name">Mailgun</div>
          <div class="driver-desc">Régions US et EU, webhooks puissants.</div>
        </div>
        <div class="driver-card fade-in">
          <div class="driver-type">Développement</div>
          <div class="driver-name">log</div>
          <div class="driver-desc">Emails écrits dans les logs, rien envoyé.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CLI Commands -->
<div style="background:var(--dark2); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
  <section id="commandes">
    <p class="section-label">CLI — djassa</p>
    <h2 class="section-title">Toutes les commandes</h2>
    <p class="section-sub">Lance <code style="color:var(--orange)">php briko help</code> depuis ton terminal pour les voir toutes.</p>

    <div class="code-block fade-in">
      <div class="code-header">
        <div class="code-dots"><span></span><span></span><span></span></div>
        Terminal
      </div>
      <div style="overflow-x:auto">
        <table class="cli-table">
          <thead><tr><th>Commande</th><th>Rôle</th></tr></thead>
          <tbody>
            <tr><td class="cmd-cell"><span class="cmd-category cat-http">HTTP</span>php briko feu</td><td>Démarrer le serveur de développement (port 8000)</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-db">DB</span>php briko migrate</td><td>Exécuter les migrations en attente</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-db">DB</span>php briko migrate:status</td><td>Voir l'état de chaque migration</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-db">DB</span>php briko migrate:rollback</td><td>Annuler le dernier batch de migrations</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-db">DB</span>php briko migrate:fresh</td><td>Supprimer tout et rejouer toutes les migrations</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-logs">SYNC</span>php briko sync</td><td>Rejouer les requêtes offline en attente</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-logs">SYNC</span>php briko sync:status</td><td>Voir la file d'attente offline</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-logs">LOGS</span>php briko logs [canal] [n]</td><td>Afficher les N dernières lignes de log</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-logs">LOGS</span>php briko logs:tail [canal]</td><td>Suivre les logs en temps réel</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-sms">SMS</span>php briko sms:test &lt;numéro&gt;</td><td>Envoyer un SMS de test au numéro donné</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-sms">SMS</span>php briko sms:otp &lt;numéro&gt;</td><td>Générer et envoyer un OTP</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-sms">SMS</span>php briko sms:driver</td><td>Voir la configuration du driver SMS actif</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-mail">MAIL</span>php briko mail:test &lt;email&gt;</td><td>Envoyer un email de test</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-mail">MAIL</span>php briko mail:driver</td><td>Voir la configuration du driver Mail actif</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-gen">GEN</span>php briko fabrique:controller &lt;Nom&gt;</td><td>Créer un controller REST complet</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-gen">GEN</span>php briko fabrique:model &lt;Nom&gt;</td><td>Créer un modèle avec méthodes statiques</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-mail">MAIL</span>php briko fabrique:mail &lt;Nom&gt;</td><td>Créer un Mailable + template HTML</td></tr>
            <tr><td class="cmd-cell"><span class="cmd-category cat-gen">GEN</span>php briko env:setup</td><td>Créer .env depuis .env.example</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<!-- Creator -->
<section style="padding-bottom:2rem">
  <p class="section-label">À propos</p>
  <h2 class="section-title" style="margin-bottom:2.5rem">Créé avec 🔥 pour l'Afrique</h2>

  <div class="creator-section fade-in">
    <div class="creator-avatar">K</div>
    <div class="creator-name">Kassé Beranger KONAN</div>
    <div class="creator-handle">@devKonan</div>
    <p class="creator-bio">
      Développeur passionné, architecte de Brikocode — un framework PHP taillé pour les réalités africaines :
      connexions instables, SMS omniprésent, APIs locales, et des développeurs qui construisent l'Afrique de demain.
    </p>
  </div>
</section>

<!-- Footer -->
<footer>
  <div style="display:flex;align-items:center;gap:.6rem;">
    <svg width="22" height="22" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="#1a1a1a"/><path d="M10 8h9.5c3.5 0 5.8 1.8 5.8 4.8 0 1.8-.9 3.2-2.4 4 2 .7 3.2 2.3 3.2 4.4 0 3.4-2.5 5.4-6.6 5.4H10V8z" fill="white" opacity=".9"/><path d="M14 12v4h5c1.4 0 2.2-.8 2.2-2s-.8-2-2.2-2h-5zm0 7.5v4.5h5.5c1.6 0 2.5-.9 2.5-2.25S21.1 19.5 19.5 19.5H14z" fill="#ff6600"/></svg>
    <span style="font-weight:700;color:var(--text)">Brikocode</span>
  </div>
  <p>Framework PHP open-source · MIT License · <span class="ci-flag">🇨🇮</span> Côte d'Ivoire</p>
  <p class="version"><?= htmlspecialchars($appName) ?> · v<?= htmlspecialchars($version) ?> · PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></p>
</footer>

<script>
// Scroll animations
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

// Responsive drivers grid
const dw = document.querySelector('.drivers-wrap');
if (dw && window.innerWidth < 800) dw.style.gridTemplateColumns = '1fr';
window.addEventListener('resize', () => {
  if (dw) dw.style.gridTemplateColumns = window.innerWidth < 800 ? '1fr' : '1fr 1fr';
});
</script>
</body>
</html>
