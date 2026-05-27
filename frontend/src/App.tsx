import { Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import Home from './pages/Home';
import MoviesPage from './pages/MoviesPage';
import MovieDetails from './pages/MovieDetails';
import Watchlist from './pages/Watchlist';
import CalendarPage from './pages/CalendarPage';
import Profile from './pages/Profile';
import Recommendations from './pages/Recommendations';
import CookieBanner from './components/CookieBanner';
import PrivacyPolicy from './pages/PrivacyPolicy';

function App() {
  return (
    <div className="min-h-screen bg-gray-50 text-neutral-900 dark:bg-neutral-950 dark:text-white font-sans selection:bg-purple-500/30 flex flex-col transition-colors duration-300">
        <Navbar />
        
        <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/movies" element={<MoviesPage />} />
            <Route path="/movie/:id" element={<MovieDetails />} />
            <Route path="/watchlist" element={<Watchlist />} />
            <Route path="/agenda" element={<CalendarPage />} />
            <Route path="/profile" element={<Profile />} />
            <Route path="/recommendations" element={<Recommendations />} />
            <Route path="/privacy" element={<PrivacyPolicy />} />
        </Routes>

        <Footer />
        <CookieBanner />
    </div>
  )
}

export default App
