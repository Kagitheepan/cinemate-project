const PrivacyPolicy = () => {
    return (
        <main className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-24 pb-16 transition-colors duration-300">
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-6">Politique de confidentialite</h1>

                <div className="space-y-8 text-gray-700 dark:text-gray-300 leading-relaxed">
                    <section>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">Responsable du traitement</h2>
                        <p>
                            Cinemate traite les donnees necessaires au fonctionnement du compte utilisateur,
                            aux preferences de films et au suivi des choix de confidentialite.
                        </p>
                    </section>

                    <section>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">Donnees collectees</h2>
                        <p>
                            Le service conserve les informations de compte, l'adresse e-mail, le nom
                            d'utilisateur, les preferences de plateformes et de genres, la watchlist et
                            l'agenda. Si le suivi est accepte, Cinemate enregistre aussi le nom
                            d'utilisateur et la date de connexion dans MongoDB.
                        </p>
                    </section>

                    <section>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">Cookies et consentement</h2>
                        <p>
                            Le choix d'acceptation ou de refus des cookies est conserve dans MongoDB avec
                            un identifiant technique, la date de decision, la version de cette politique et,
                            lorsque l'utilisateur est connecte, son nom d'utilisateur. Le refus n'empeche pas
                            l'utilisation du site et bloque l'enregistrement des logs de connexion.
                        </p>
                    </section>

                    <section>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">Authentification</h2>
                        <p>
                            Le jeton de connexion est stocke dans un cookie HttpOnly afin de ne pas etre
                            lisible par le JavaScript du navigateur. Ce choix reduit le risque de vol du
                            jeton en cas de faille XSS.
                        </p>
                    </section>

                    <section>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">Droits des utilisateurs</h2>
                        <p>
                            L'utilisateur peut demander l'acces, la rectification ou la suppression de ses
                            donnees personnelles. Les logs de connexion ne sont pas affiches dans
                            l'application frontend et restent consultables uniquement depuis la base MongoDB
                            par l'administrateur.
                        </p>
                    </section>
                </div>
            </div>
        </main>
    );
};

export default PrivacyPolicy;
