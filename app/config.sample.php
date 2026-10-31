<?php
/* L'Armoire à Cuillères — configuration du serveur.
   Copier ce fichier en config.php (à côté de lui, dans app/) et le remplir. config.php n'est jamais publié. */
return [
    // Adresse publique du site, sans « / » final
    'base_url' => 'https://www.larmoireacuilleres.com',

    // Base de données.
    // SQLite (le plus simple, rien à créer) : le fichier vit dans app/data/, hors du dossier public.
    'db' => ['dsn' => 'sqlite:' . __DIR__ . '/data/armoire.sqlite', 'user' => null, 'pass' => null],
    // MySQL OVH (Hébergement > Bases de données) : décommenter et remplir.
    // 'db' => ['dsn' => 'mysql:host=xxxxx.mysql.db;dbname=xxxxx;charset=utf8mb4', 'user' => 'xxxxx', 'pass' => 'xxxxx'],

    // Code demandé UNE seule fois pour créer le premier compte administrateur (/admin).
    // Mettre une longue phrase au hasard ; on peut l'effacer une fois le compte créé.
    'setup_token' => 'CHANGER-CETTE-PHRASE-AVANT-MISE-EN-LIGNE',

    // Courriels : la boutique reçoit les nouveaux tickets ; les clients reçoivent les réponses.
    'mail_to' => 'hello@larmoireacuilleres.com',
    'mail_from' => 'no-reply@larmoireacuilleres.com', // une adresse du domaine (sinon OVH refuse l'envoi)

    // Derrière un proxy/CDN de confiance seulement : nom de l'en-tête qui porte la vraie IP (sinon null)
    'trusted_ip_header' => null,

    // false en production : n'affiche jamais les erreurs PHP aux visiteurs
    'debug' => false,
];
