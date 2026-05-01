<?php

return [
    'up' => function (\PDO $pdo): void {
        // Ajoute la colonne api_token si elle n'existe pas encore
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN `api_token` VARCHAR(64) NULL DEFAULT NULL");
        } catch (\Throwable) {
            // Colonne déjà présente — ignoré
        }
    },

    'down' => function (\PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE users DROP COLUMN api_token");
        } catch (\Throwable) {
            // Colonne absente — ignoré
        }
    },
];
