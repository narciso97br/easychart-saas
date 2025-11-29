<?php

session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Lang.php';
require_once __DIR__ . '/../app/core/Router.php';

Router::dispatch();