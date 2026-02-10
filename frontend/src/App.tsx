import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import PlaceholderPage from './pages/PlaceholderPage'; // Importar PlaceholderPage
import './App.css';
import React, { useContext } from 'react';
import { AuthContext } from './context/AuthContext';

const ProtectedRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isAuthenticated } = useContext(AuthContext);

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
};

function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <DashboardPage />
          </ProtectedRoute>
        }
      />
      {/* Novas rotas para as seções */}
      <Route
        path="/clients"
        element={
          <ProtectedRoute>
            <PlaceholderPage title="Gestão de Clientes" description="Em breve: Funcionalidades de CRUD de clientes." />
          </ProtectedRoute>
        }
      />
      <Route
        path="/jobs"
        element={
          <ProtectedRoute>
            <PlaceholderPage title="Gestão de Trabalhos" description="Em breve: Funcionalidades de CRUD de trabalhos." />
          </ProtectedRoute>
        }
      />
      <Route
        path="/services"
        element={
          <ProtectedRoute>
            <PlaceholderPage title="Gestão de Serviços" description="Em breve: Funcionalidades de CRUD de serviços." />
          </ProtectedRoute>
        }
      />
      <Route
        path="/users"
        element={
          <ProtectedRoute>
            <PlaceholderPage title="Gestão de Usuários" description="Em breve: Funcionalidades de CRUD de usuários." />
          </ProtectedRoute>
        }
      />
      <Route
        path="/settings"
        element={
          <ProtectedRoute>
            <PlaceholderPage title="Configurações do Sistema" description="Em breve: Páginas de configuração." />
          </ProtectedRoute>
        }
      />
      {/* Rota padrão: redireciona para login */}
      <Route path="/" element={<Navigate to="/login" replace />} />
    </Routes>
  );
}

export default App;
