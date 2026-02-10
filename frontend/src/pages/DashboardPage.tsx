import React from 'react';
import { Box, Typography, Container, Button, Grid, Card, CardContent, CardActions } from '@mui/material';
import { useAuth } from '../context/AuthContext';
import { Link } from 'react-router-dom'; // Importar Link

const DashboardPage: React.FC = () => {
  const auth = useAuth();

  const handleLogout = async () => {
    try {
      const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/crm-terreiro/public';
      const response = await fetch(`${API_BASE_URL}/api/logout`, {
        method: 'GET',
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
      auth.logout();
    }
  };

  const dashboardItems = [
    { title: 'Clientes', description: 'Gerencie seus contatos e clientes.', path: '/clients' },
    { title: 'Trabalhos', description: 'Acompanhe projetos e tarefas.', path: '/jobs' },
    { title: 'Serviços', description: 'Configure os serviços oferecidos.', path: '/services' },
    { title: 'Usuários', description: 'Administre usuários e permissões.', path: '/users' },
    { title: 'Configurações', description: 'Ajuste as configurações do sistema.', path: '/settings' },
  ];

  return (
    <Container component="main" maxWidth="lg">
      <Box
        sx={{
          marginTop: 4,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
        }}
      >
        <Typography component="h1" variant="h4" gutterBottom>
          Dashboard CRM Moderno
        </Typography>

        <Grid container spacing={3} sx={{ mt: 3 }}>
          {dashboardItems.map((item, index) => (
            <Grid item xs={12} sm={6} md={4} key={index}>
              <Card raised sx={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
                <CardContent sx={{ flexGrow: 1 }}>
                  <Typography gutterBottom variant="h5" component="div">
                    {item.title}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {item.description}
                  </Typography>
                </CardContent>
                <CardActions>
                  <Button size="small" component={Link} to={item.path}>
                    Acessar
                  </Button>
                </CardActions>
              </Card>
            </Grid>
          ))}
        </Grid>

        <Button
          variant="outlined"
          color="secondary"
          sx={{ mt: 4 }}
          onClick={handleLogout}
        >
          Logout
        </Button>
      </Box>
    </Container>
  );
};

export default DashboardPage;
