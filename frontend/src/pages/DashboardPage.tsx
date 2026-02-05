import React from 'react';
import { Box, Typography, Container, Button } from '@mui/material';
import { useAuth } from '../context/AuthContext'; // Importar useAuth

const DashboardPage: React.FC = () => {
  const auth = useAuth(); // Usar o hook useAuth

  const handleLogout = async () => {
    // Call API to invalidate session/token if applicable
    // This part should be handled within AuthContext.logout for a clean separation.
    // For now, we simulate the API call here and then call auth.logout() to update state.
    try {
      const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/crm-terreiro/public';
      const response = await fetch(`${API_BASE_URL}/api/logout`, {
        method: 'GET', // Or POST, depending on your API design
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        console.log('Logout API call successful');
      } else {
        const errorData = await response.json();
        console.error('Logout API call failed:', errorData.message);
      }
    } catch (error) {
      console.error('Network error during logout API call:', error);
    } finally {
      auth.logout(); // Call logout from auth context regardless of API call success/failure for client-side state update
    }
  };

  return (
    <Container component="main" maxWidth="md">
      <Box
        sx={{
          marginTop: 8,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
        }}
      >
        <Typography component="h1" variant="h4" gutterBottom>
          CRM Dashboard
        </Typography>
        <Typography variant="body1" paragraph>
          Bem-vindo ao seu novo painel de controle!
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Esta é uma página de placeholder. Em breve, ela será preenchida com
          conteúdo dinâmico e gráficos para gerenciar seus clientes, trabalhos e serviços.
        </Typography>
        <Button
          variant="contained"
          color="primary"
          sx={{ mt: 3 }}
          onClick={() => alert('Em breve!')}
        >
          Ver Clientes
        </Button>
        <Button
          variant="outlined"
          color="secondary"
          sx={{ mt: 2 }}
          onClick={handleLogout}
        >
          Logout
        </Button>
      </Box>
    </Container>
  );
};

export default DashboardPage;
