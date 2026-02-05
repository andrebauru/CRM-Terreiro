<?php
// This view could redirect to /dashboard if authenticated, or display a public welcome.
// For now, it will prompt for login.
header('Location: ' . ROUTE_BASE . '/login');
exit();
