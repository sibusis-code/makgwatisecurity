<?php
require_once __DIR__ . '/auth.php';
mgw_session_start();
session_destroy();
header('Location: login.php?out=1');
exit;
