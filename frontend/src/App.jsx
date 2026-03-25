import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import api from './api/axios';
import { AnimatePresence } from 'framer-motion';
import Navbar from './components/Navbar';
import PageTransition from './components/PageTransition';
import Home from './pages/Home';
import Portraits from './pages/Portraits';
import Events from './pages/Events';
import About from './pages/About';
import AlbumDetail from './pages/AlbumDetail';
import AdminDashboard from './pages/AdminDashboard';
import AdminLogin from './pages/AdminLogin';

// Inner component so we can use useLocation (must be inside <Router>)
function AnimatedRoutes({ settings, setIsAdmin }) {
  const location = useLocation();
  return (
    <AnimatePresence mode="wait">
      <Routes location={location} key={location.pathname}>
        <Route path="/"                 element={<PageTransition><Home settings={settings} /></PageTransition>} />
        <Route path="/portraits"        element={<PageTransition><Portraits settings={settings} /></PageTransition>} />
        <Route path="/events"           element={<PageTransition><Events settings={settings} /></PageTransition>} />
        <Route path="/album/:id"        element={<PageTransition><AlbumDetail settings={settings} /></PageTransition>} />
        <Route path="/about"            element={<PageTransition><About settings={settings} /></PageTransition>} />
        <Route path="/admin/josef/login"      element={<PageTransition><AdminLogin onLogin={() => setSettings(prev => ({...prev}))} setIsAdmin={setIsAdmin} /></PageTransition>} />
        <Route path="/admin/dashboard"  element={<PageTransition><AdminDashboard setIsAdmin={setIsAdmin} /></PageTransition>} />
      </Routes>
    </AnimatePresence>
  );
}

function App() {
  const [settings, setSettings] = useState(null);
  const [isAdmin, setIsAdmin] = useState(!!localStorage.getItem('admin_token'));

  useEffect(() => {
    const handleGlobalSecurity = (e) => {
      // 1. Block Right Click
      if (e.type === 'contextmenu') {
        e.preventDefault();
        return false;
      }

      // 2. Block Keyboard Shortcuts
      if (e.type === 'keydown') {
        // Ctrl+S (Save), Ctrl+U (View Source), Ctrl+P (Print)
        if (e.ctrlKey && (e.key === 's' || e.key === 'u' || e.key === 'p')) {
          e.preventDefault();
          return false;
        }
        // F12 (DevTools), Ctrl+Shift+I (Inspect)
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
          e.preventDefault();
          return false;
        }
        // PrintScreen (Optional - some browsers allow blocking this)
        if (e.key === 'PrintScreen') {
          navigator.clipboard.writeText(''); // Clear clipboard
          alert('Screen capture is disabled to protect artist copyright.');
        }
      }
    };

    // Temporarily disabled for debugging
    // window.addEventListener('contextmenu', handleGlobalSecurity);
    // window.addEventListener('keydown', handleGlobalSecurity);
    
    return () => {
      // window.removeEventListener('contextmenu', handleGlobalSecurity);
      // window.removeEventListener('keydown', handleGlobalSecurity);
    };
  }, []);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const res = await api.get('/settings');
        setSettings(res.data);
        
        // Apply Global Styles
        const root = document.documentElement;
        if (res.data.primary_color) root.style.setProperty('--primary', res.data.primary_color);
        if (res.data.accent_color) root.style.setProperty('--accent', res.data.accent_color);
        if (res.data.bg_color) root.style.setProperty('--bg', res.data.bg_color);
        if (res.data.site_title) document.title = res.data.site_title;
      } catch (e) {
        console.error("Failed to load settings", e);
      }
    };
    fetchSettings();
  }, []);

  return (
    <Router>
      <div className="app-layout">
        <Navbar settings={settings} isAdmin={isAdmin} />
        <div className="main-content-wrapper">
          <main className="main-content">
            <AnimatedRoutes settings={settings} setIsAdmin={setIsAdmin} />
          </main>
          <footer className="footer">
            {settings?.footer_copy || `© ${new Date().getFullYear()} Josef Nhidi Photography. All Rights Reserved.`}
          </footer>
        </div>
      </div>
    </Router>
  );
}

export default App;
