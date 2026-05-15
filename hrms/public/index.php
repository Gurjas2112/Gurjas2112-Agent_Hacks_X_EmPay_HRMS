<?php
/**
 * EmPay HRMS - Main Entry Point
 * All requests are routed through this file.
 */

// Load configuration
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../utils/demo_provisioner.php';

// Auto-sync demo data for Joy Kapoor
provisionDemoData();

// Load the router
require_once __DIR__ . '/router.php';
