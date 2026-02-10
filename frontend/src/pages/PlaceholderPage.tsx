import React from 'react';
import { Container, Box, Typography, Button } from '@mui/material';
import { useNavigate } from 'react-router-dom';

interface PlaceholderPageProps {
  title: string;
  description: string;
}

const PlaceholderPage: React.FC<PlaceholderPageProps> = ({ title, description }) => {
  const navigate = useNavigate();

  return (
    <Container component="main" maxWidth="md">
      <Box
        sx={{
          marginTop: 8,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          textAlign: 'center',
        }}
      >
        <Typography component="h1" variant="h4" gutterBottom>
          {title}
        </Typography>
        <Typography variant="body1" paragraph>
          {description}
        </Typography>
        <Button variant="contained" sx={{ mt: 3 }} onClick={() => navigate('/dashboard')}>
          Voltar ao Dashboard
        </Button>
      </Box>
    </Container>
  );
};

export default PlaceholderPage;
