<?php
/**
 * admin/_header.php
 * Template de cabeçalho compartilhado — incluir APÓS requireLogin()
 * Variável $pageTitle deve estar definida na página que inclui este arquivo
 */
$csrf  = csrfToken();
$flash = getFlash();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($pageTitle ?? 'Admin') ?> — HSE Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-wrap">

  <!-- ===== Sidebar ===== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="../images/hse.png" alt="Logo HSE" class="sidebar-logo">
      <span>Painel<br>Administrativo</span>
    </div>

    <nav class="sidebar-nav" aria-label="Menu administrativo">
      <div class="nav-section">Principal</div>
      <a href="index.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge"></i> Dashboard
      </a>

      <div class="nav-section">Cadastros</div>
      <a href="especialidades.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'especialidades.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-stethoscope"></i> Especialidades
      </a>
      <a href="medicos.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'medicos.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-doctor"></i> Médicos
      </a>
      <a href="convenios.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'convenios.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-shield-heart"></i> Convênios
      </a>
      <a href="depoimentos.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'depoimentos.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-comment-dots"></i> Depoimentos
      </a>

      <a href="avisos.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'avisos.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-bell"></i> Avisos / Modal
      </a>

      <div class="nav-section">Conta</div>
      <a href="senha.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'senha.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-key"></i> Alterar Senha
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="../index.php" target="_blank" rel="noopener noreferrer">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver site
      </a>
      <a href="logout.php" class="logout-link">
        <i class="fa-solid fa-right-from-bracket"></i> Sair
      </a>
    </div>
  </aside>

  <!-- ===== Main ===== -->
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir/fechar menu">
        <i class="fa-solid fa-bars"></i>
      </button>
      <span class="page-title"><?= esc($pageTitle ?? '') ?></span>
      <div class="topbar-user">
        <i class="fa-solid fa-circle-user"></i>
        <?= esc(adminName()) ?>
      </div>
    </header>

    <div class="admin-content">

      <?php if ($flash): ?>
      <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'error') ?>">
        <i class="fa-solid fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
        <?= esc($flash['msg']) ?>
      </div>
      <?php endif; ?>
