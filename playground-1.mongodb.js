// Sélectionne la base MongoDB utilisée par docker-compose.yml
use('cinemate_movies');

// Affiche tous les logs de connexion
db.connection_logs.find();

// Affiche les choix cookies RGPD
db.cookie_consents.find();
