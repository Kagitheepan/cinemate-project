import { lazy, Suspense } from 'react';
import { Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import CookieBanner from './components/CookieBanner';

// Lazy loading the pages
const Home = lazy(() => import('./pages/Home'));
const MoviesPage = lazy(() => import('./pages/MoviesPage'));
const MovieDetails = lazy(() => import('./pages/MovieDetails'));
const Watchlist = lazy(() => import('./pages/Watchlist'));
const CalendarPage = lazy(() => import('./pages/CalendarPage'));
const Profile = lazy(() => import('./pages/Profile'));
const Recommendations = lazy(() => import('./pages/Recommendations'));
const PrivacyPolicy = lazy(() => import('./pages/PrivacyPolicy'));

// A simple loading fallback
const PageLoader = () => (
  <div className="flex-grow flex items-center justify-center pt-24 pb-12">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 dark:border-purple-500"></div>
  </div>
);

function App() {
  return (
    <div className="min-h-screen bg-gray-50 text-neutral-900 dark:bg-neutral-950 dark:text-white font-sans selection:bg-purple-500/30 flex flex-col transition-colors duration-300">
        <Navbar />
        
        <Suspense fallback={<PageLoader />}>
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
        </Suspense>

        <Footer />
        <CookieBanner />
    </div>
  )
}

export default App
