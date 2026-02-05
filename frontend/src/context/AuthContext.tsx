import React, { createContext, useState, useContext, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';

interface AuthContextType {
  isAuthenticated: boolean;
  login: (token: string, userData: any) => void; // userData could be more specific
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(() => {
    // Check if user data exists in local storage on initial load
    return !!localStorage.getItem('user');
  });
  const navigate = useNavigate();

  const login = (token: string, userData: any) => {
    // In a real app, you'd store the token securely (e.g., HTTP-only cookies, or more complex local storage handling)
    // For now, we'll simulate by setting a flag and user data
    localStorage.setItem('user', JSON.stringify(userData)); // Store user data
    setIsAuthenticated(true);
    navigate('/dashboard'); // Redirect to dashboard after login
  };

  const logout = async () => {
    // Call API to invalidate session/token if applicable
    // For now, it will use the apiLogout from the DashboardPage.tsx
    // Once logout API call is successful or client-side logic is done:
    localStorage.removeItem('user');
    setIsAuthenticated(false);
    navigate('/login'); // Redirect to login after logout
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
