<nav class="navbar navbar-dark navbar-expand-lg bg-dark mb-2">
    <div class="container-fluid">
        <!-- Branding -->
        <a class="navbar-brand" alt="logo" class="d-inline-block align-text-top"
            href="<?= $config['root_path']; ?>"><img src="<?= $config['root_path'] ?>/img/nw-logo.png" height="24"><span
                class="d-none d-md-inline p-2">Neverer</span></a>
        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUserMenu"
            aria-controls="navbarUserMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php

use UI\DisplayMessage;

    if (empty($_SESSION['USER'])) {
?>
        <!-- User menu (not logged in) -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarUserMenu">
            <ul class="navbar-nav ml-auto mb-2 mb-lg-0" text-right pull-right>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="<?= $config['root_path'] ?>/nw/faq">FAQ</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="<?= $config['root_path'] ?>/login.php" role="button">Login</a>
                </li>
            </ul>
        </div>
        <?php
    } else {
?>
        <!-- Template menu item -->
        <div style='display:none;' id='template-holder'>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li>
                    <a class="nav-link" aria-current="page" href=""></a>
                </li>
            </ul>
        </div>
        <!-- Full menu (logged in) -->
        <div class="collapse navbar-collapse" id="navbarUserMenu">
            <!-- Create button -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <div class='vr bg-light'></div>
                <li>
                    <a class="nav-link" aria-current="page" href="<?= $config['root_path'] ?>/crossword/create">
                        New Crossword
                    </a>
                </li>
                <div class='vr bg-light'></div>
                <li>
                    <a class="nav-link" aria-current="page" target="_blank"
                        href="<?= $config['root_path'] ?>/dictionary/index">
                        Dictionaries
                    </a>
                </li>
            </ul>
            <!-- User menu (logged in) -->
            <div class="justify-content-end" id="navbarUserMenu2">
                <ul class="navbar-nav ml-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="<?= $config['root_path'] ?>/nw/faq">FAQ</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span class='d-none d-md-inline'><?= $user->display_name ?></span>
                            <span class='d-md-none initial-display'><?= substr($user->display_name,0,1) ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($user->identifier == 'almcnicoll'): ?><li><a class="dropdown-item"
                                    href="<?= $config['root_path'] ?>/admin/dashboard">Admin</a></li><?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $config['root_path'] ?>/account/manage">My
                                    account</a></li>
                            <li><a class="dropdown-item" href="<?= $config['root_path'] ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }
?>
    </div>
    </div>
</nav>
<?php
  if(isset($_SESSION[DisplayMessage::SESSION_KEY])) {
    echo DisplayMessage::getFormattedList($_SESSION[DisplayMessage::SESSION_KEY]);
  }
?>