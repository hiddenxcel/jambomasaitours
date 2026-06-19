<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
session_destroy();
redirect(url('admin/login.php'));
