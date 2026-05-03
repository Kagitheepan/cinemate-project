import { Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import Home from './pages/Home';
import MoviesPage from './pages/MoviesPage';
import MovieDetails from './pages/MovieDetails';
import Watchlist from './pages/Watchlist';
import CalendarPage from './pages/CalendarPage';
import Profile from './pages/Profile';

function App() {
  return (
    <div className="min-h-screen bg-neutral-950 font-sans text-white selection:bg-purple-500/30 flex flex-col">
        <Navbar />
        
        <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/movies" element={<MoviesPage />} />
            <Route path="/movie/:id" element={<MovieDetails />} />
            <Route path="/watchlist" element={<Watchlist />} />
            <Route path="/agenda" element={<CalendarPage />} />
            <Route path="/profile" element={<Profile />} />
        </Routes>

        <Footer />
    </div>
  )
}

export default App
