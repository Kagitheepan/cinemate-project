
const Footer = () => {
    return (
        <footer className="border-t border-white/5 py-12 bg-neutral-900 mt-24">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    <div className="space-y-4">
                        <span className="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-600 bg-clip-text text-transparent">
                            Cinemate
                        </span>
                        <p className="text-gray-400 text-sm">
                            Votre compagnon ultime pour découvrir, suivre et partager votre passion pour le cinéma et les séries.
                        </p>
                    </div>

                    <div>
                        <h4 className="font-semibold text-white mb-4">Navigation</h4>
                        <ul className="space-y-2 text-sm text-gray-400">
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Accueil</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Films</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Séries</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Agenda</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-semibold text-white mb-4">Communauté</h4>
                        <ul className="space-y-2 text-sm text-gray-400">
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Forum</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Critiques</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Listes</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-semibold text-white mb-4">Légal</h4>
                        <ul className="space-y-2 text-sm text-gray-400">
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Confidentialité</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Conditions</a></li>
                            <li><a href="#" className="hover:text-purple-400 transition-colors">Contact</a></li>
                        </ul>
                    </div>
                </div>
                
                <div className="border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
                    <p>&copy; {new Date().getFullYear()} Cinemate. All rights reserved.</p>
                    <div className="flex space-x-6 mt-4 md:mt-0">
                        {/* Placeholder for social icons */}
                        <a href="#" className="hover:text-white transition-colors">Twitter</a>
                        <a href="#" className="hover:text-white transition-colors">Instagram</a>
                        <a href="#" className="hover:text-white transition-colors">GitHub</a>
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
