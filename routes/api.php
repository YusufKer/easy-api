<?php

// Define all API routes here
$router->get('/api/protein', 'ProteinController', 'index');
$router->get('/api/flavours', 'FlavoursController', 'index');
$router->get('/api/cuts', 'CutsController', 'index');
