<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bienvenue</title>
  <style>
    body      { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper  { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .header   { background: #ff6600; padding: 32px; text-align: center; }
    .header h1{ color: #fff; margin: 0; font-size: 28px; letter-spacing: 1px; }
    .body     { padding: 32px; color: #333; line-height: 1.6; }
    .body h2  { color: #ff6600; }
    .btn      { display: inline-block; margin: 24px 0; padding: 14px 32px; background: #ff6600; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
    .footer   { background: #f0f0f0; padding: 16px 32px; font-size: 12px; color: #888; text-align: center; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1><?= htmlspecialchars(env('APP_NAME', 'Brikocode')) ?></h1>
    </div>
    <div class="body">
      <h2>Bienvenue, <?= htmlspecialchars($user['nom'] ?? $user['name'] ?? 'Ami(e)') ?> ! 👋</h2>
      <p>Ton compte a été créé avec succès. Tu fais maintenant partie de notre communauté.</p>
      <p>Pour commencer, connecte-toi à ton espace :</p>
      <a href="<?= htmlspecialchars(env('APP_URL', '#')) ?>" class="btn">Accéder à mon compte</a>
      <p>Si tu n'es pas à l'origine de cette inscription, ignore cet email.</p>
    </div>
    <div class="footer">
      © <?= date('Y') ?> <?= htmlspecialchars(env('APP_NAME', 'Brikocode')) ?> —
      Fait avec 🔥 en Côte d'Ivoire
    </div>
  </div>
</body>
</html>
