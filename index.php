<?php
/**
 * index.php - Landing Page Principal
 * Hospital Santo Expedito - APAS
 * PHP puro + PDO + MySQL
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

// ============================================================
// Processamento do formulário de agendamento (POST)
// ============================================================
$formMsg   = '';
$formType  = '';
$formData  = []; // repopular campos em caso de erro

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agendar') {

    // Coleta e sanitização básica dos dados de entrada
    $formData['nome']            = trim($_POST['nome']            ?? '');
    $formData['email']           = trim($_POST['email']           ?? '');
    $formData['telefone']        = trim($_POST['telefone']        ?? '');
    $formData['especialidade_id']= (int) ($_POST['especialidade_id'] ?? 0);
    $formData['mensagem']        = trim($_POST['mensagem']        ?? '');
    $formData['data_desejada']   = trim($_POST['data_desejada']   ?? '');

    // --- Validação server-side ---
    $errors = [];

    if (mb_strlen($formData['nome']) < 3) {
        $errors[] = 'Nome deve ter pelo menos 3 caracteres.';
    }
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    $telDigits = preg_replace('/\D/', '', $formData['telefone']);
    if (!$telDigits || mb_strlen($telDigits) < 10) {
        $errors[] = 'Informe um telefone válido com DDD.';
    }
    if ($formData['especialidade_id'] <= 0) {
        $errors[] = 'Selecione uma especialidade.';
    }
    if (!$formData['data_desejada'] || !strtotime($formData['data_desejada'])) {
        $errors[] = 'Informe uma data válida.';
    } elseif (strtotime($formData['data_desejada']) < strtotime('today')) {
        $errors[] = 'A data desejada deve ser hoje ou futura.';
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            // Verifica se especialidade existe
            $stmt = $pdo->prepare('SELECT id FROM especialidades WHERE id = ? AND ativo = 1');
            $stmt->execute([$formData['especialidade_id']]);
            if (!$stmt->fetch()) {
                $errors[] = 'Especialidade inválida.';
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO agendamentos (nome, email, telefone, especialidade_id, mensagem, data_desejada)
                     VALUES (:nome, :email, :telefone, :especialidade_id, :mensagem, :data_desejada)'
                );
                $ins->execute([
                    ':nome'             => $formData['nome'],
                    ':email'            => $formData['email'],
                    ':telefone'         => $telDigits,
                    ':especialidade_id' => $formData['especialidade_id'],
                    ':mensagem'         => $formData['mensagem'],
                    ':data_desejada'    => $formData['data_desejada'],
                ]);
                $formMsg  = 'Agendamento solicitado com sucesso! Em breve nossa equipe entrará em contato para confirmação.';
                $formType = 'success';
                $formData = []; // limpa campos após sucesso
            }
        } catch (PDOException $e) {
            error_log('Erro ao salvar agendamento: ' . $e->getMessage());
            $formMsg  = 'Ocorreu um erro ao processar seu agendamento. Tente novamente.';
            $formType = 'error';
        }
    } else {
        $formMsg  = implode('<br>', $errors);
        $formType = 'error';
    }
}

// ============================================================
// Busca de dados do banco para renderização
// ============================================================
$especialidades = [];
$medicos        = [];
$convenios      = [];
$depoimentos    = [];

try {
    $pdo = getDB();

    $especialidades = $pdo->query(
        'SELECT id, nome, descricao, icone FROM especialidades WHERE ativo = 1 ORDER BY nome'
    )->fetchAll();

    $medicos = $pdo->query(
        'SELECT id, nome, especialidade, foto, crm, descricao FROM medicos WHERE ativo = 1 ORDER BY nome'
    )->fetchAll();

    $convenios = $pdo->query(
        'SELECT id, nome, logo FROM convenios WHERE ativo = 1 ORDER BY nome'
    )->fetchAll();

    $depoimentos = $pdo->query(
        'SELECT id, nome, texto, avaliacao, data FROM depoimentos WHERE ativo = 1 ORDER BY data DESC LIMIT 6'
    )->fetchAll();

} catch (PDOException $e) {
    error_log('Erro ao carregar dados: ' . $e->getMessage());
}

// Helper: retorna estrelas HTML
function stars(int $n): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa-' . ($i <= $n ? 'solid' : 'regular') . ' fa-star"></i>';
    }
    return $html;
}

// Helper: iniciais para avatar
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $ini   = mb_substr($parts[0], 0, 1);
    if (count($parts) > 1) $ini .= mb_substr($parts[count($parts) - 1], 0, 1);
    return mb_strtoupper($ini);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Hospital Santo Expedito - APAS. Cuidando da sua saúde com excelência, tecnologia e humanização. Agende sua consulta online.">
  <meta name="keywords"    content="hospital, saúde, consulta, especialidades, São Paulo, APAS">
  <meta name="author"      content="Hospital Santo Expedito - APAS">

  <!-- Open Graph -->
  <meta property="og:title"       content="Hospital Santo Expedito - APAS">
  <meta property="og:description" content="Cuidando da sua saúde com excelência, tecnologia e humanização.">
  <meta property="og:type"        content="website">

  <title>Hospital Santo Expedito - APAS | Cuidando da sua Saúde</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/style.css">

  <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body>

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<header id="navbar" role="banner" aria-label="Navegação principal">
  <div class="container nav-inner">

    <!-- Logo -->
    <a href="#hero" class="nav-logo" aria-label="<?= esc(SITE_NAME) ?>">
      <img src="images/hse.png" alt="Logo Hospital Santo Expedito - APAS" class="nav-logo-img">
    </a>

    <!-- Menu desktop -->
    <nav class="nav-menu" role="navigation" aria-label="Menu principal">
      <a href="#sobre"         class="nav-link">Sobre</a>
      <a href="#especialidades"class="nav-link">Especialidades</a>
      <a href="#equipe"        class="nav-link">Equipe</a>
      <a href="#convenios"     class="nav-link">Convênios</a>
      <a href="#contato"       class="nav-link">Contato</a>
    </nav>

    <!-- Ações -->
    <div class="nav-actions">
      <a href="tel:<?= preg_replace('/\D/', '', SITE_PHONE) ?>" class="btn btn-outline" style="padding:9px 18px;font-size:.82rem;">
        <i class="fa-solid fa-phone" aria-hidden="true"></i>
        <?= esc(SITE_PHONE) ?>
      </a>
      <a href="#contato" class="btn btn-primary">
        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
        Agendar Consulta
      </a>
    </div>

    <!-- Hamburguer mobile -->
    <button class="nav-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="nav-menu-mobile">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section id="hero" role="main" aria-label="Apresentação do hospital">
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">
        <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
        Referência em Saúde na Baixada Santista
      </div>

      <h1>
        Cuidamos de você com<br>
        <span>Excelência &amp; Humanização</span>
      </h1>

      <p>
        O Hospital Santo Expedito &mdash; APAS oferece atendimento médico de alta qualidade
        com tecnologia de ponta, equipe especializada e um compromisso inabalável com
        o bem-estar de cada paciente.
      </p>

      <div class="hero-cta">
        <a href="#contato" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
          Agendar Consulta
        </a>
        <a href="#sobre" class="btn btn-outline btn-lg">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          Saiba Mais
        </a>
      </div>

      <div class="hero-stats">
        <div class="hero-stat">
          <strong data-count="25" data-suffix="+">25+</strong>
          <span>Anos de Experiência</span>
        </div>
        <div class="hero-stat">
          <strong data-count="150" data-suffix="+">150+</strong>
          <span>Médicos Especialistas</span>
        </div>
        <div class="hero-stat">
          <strong data-count="50000" data-suffix="+">50.000+</strong>
          <span>Pacientes Atendidos</span>
        </div>
        <div class="hero-stat">
          <strong data-count="98" data-suffix="%">98%</strong>
          <span>Satisfação dos Pacientes</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     SOBRE
     ============================================================ -->
<section id="sobre" aria-label="Sobre o hospital">
  <div class="container">
    <div class="section-title reveal">
      <h2>Sobre o <span>Hospital Santo Expedito</span></h2>
      <p>Há 25 anos cuidando de quem mais amamos com dedicação, tecnologia e respeito humano.</p>
    </div>
    <div class="section-divider reveal"></div>

    <div class="sobre-grid">
      <div class="sobre-img-wrap reveal">
        <img
          src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=700&q=80"
          alt="Equipe médica do Hospital Santo Expedito"
          loading="lazy"
        >
        <div class="sobre-badge">
          <strong>25</strong>
          <span>anos de história</span>
        </div>
      </div>

      <div class="sobre-text reveal">
        <h2>Comprometidos com a sua <span>saúde e bem-estar</span></h2>
        <p>
          Fundado em 2001, o Hospital Santo Expedito &mdash; APAS nasceu com a missão de oferecer
          atendimento médico humano, acessível e de excelência para toda a comunidade.
          Ao longo de 25 anos, construímos uma trajetória baseada na confiança dos pacientes,
          na qualificação de nossa equipe e no constante investimento em infraestrutura e tecnologia.
        </p>
        <p>
          Contamos com mais de 150 médicos especialistas, 12 especialidades médicas,
          centro cirúrgico moderno, UTI adulto e neonatal, além de unidades de diagnóstico por imagem
          e laboratório próprio.
        </p>

        <div class="mvv-cards">
          <div class="mvv-card">
            <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
            <h4>Missão</h4>
            <p>Promover saúde e qualidade de vida com atendimento humanizado e tecnologia de ponta.</p>
          </div>
          <div class="mvv-card">
            <i class="fa-solid fa-eye" aria-hidden="true"></i>
            <h4>Visão</h4>
            <p>Ser referência nacional em saúde, reconhecido pela excelência e inovação constante.</p>
          </div>
          <div class="mvv-card">
            <i class="fa-solid fa-star" aria-hidden="true"></i>
            <h4>Valores</h4>
            <p>Ética, transparência, respeito ao paciente e comprometimento com resultados.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     ESPECIALIDADES
     ============================================================ -->
<section id="especialidades" aria-label="Especialidades médicas">
  <div class="container">
    <div class="section-title reveal">
      <h2>Nossas <span>Especialidades</span></h2>
      <p>Contamos com uma ampla gama de especialidades médicas para cuidar de toda a sua família.</p>
    </div>
    <div class="section-divider reveal"></div>

    <?php if (!empty($especialidades)): ?>
    <div class="esp-grid">
      <?php foreach ($especialidades as $esp): ?>
      <div class="esp-card reveal">
        <div class="esp-icon" aria-hidden="true">
          <i class="fa-solid <?= esc($esp['icone']) ?>"></i>
        </div>
        <h3><?= esc($esp['nome']) ?></h3>
        <p><?= esc($esp['descricao']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center" style="color:var(--gray-text);margin-top:24px;">
      Nenhuma especialidade cadastrada no momento.
    </p>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     EQUIPE MÉDICA
     ============================================================ -->
<section id="equipe" aria-label="Equipe médica">
  <div class="container">
    <div class="section-title reveal">
      <h2>Nossa <span>Equipe Médica</span></h2>
      <p>Profissionais altamente qualificados, comprometidos com o cuidado integral e humanizado.</p>
    </div>
    <div class="section-divider reveal"></div>

    <?php if (!empty($medicos)): ?>
    <div class="team-grid">
      <?php foreach ($medicos as $med): ?>
      <article class="team-card reveal">
        <div class="photo-wrap">
          <?php
            // Verifica se o arquivo de foto existe no servidor
            $photoPath = __DIR__ . '/' . $med['foto'];
            if (file_exists($photoPath) && is_file($photoPath)):
          ?>
          <img
            src="<?= esc($med['foto']) ?>"
            alt="Foto de <?= esc($med['nome']) ?>"
            loading="lazy"
          >
          <?php else: ?>
          <div class="photo-placeholder" aria-hidden="true">
            <i class="fa-solid fa-user-doctor"></i>
          </div>
          <?php endif; ?>
        </div>
        <div class="team-card-body">
          <h3><?= esc($med['nome']) ?></h3>
          <span class="esp-tag"><?= esc($med['especialidade']) ?></span>
          <p class="crm"><i class="fa-solid fa-id-card" aria-hidden="true"></i> <?= esc($med['crm']) ?></p>
          <?php if (!empty($med['descricao'])): ?>
          <p><?= esc($med['descricao']) ?></p>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center" style="color:var(--gray-text);margin-top:24px;">
      Nenhum médico cadastrado no momento.
    </p>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     CONVÊNIOS
     ============================================================ -->
<section id="convenios" aria-label="Convênios aceitos">
  <div class="container">
    <div class="section-title reveal">
      <h2>Convênios <span>Aceitos</span></h2>
      <p>Trabalhamos com os principais planos de saúde do Brasil para facilitar o seu atendimento.</p>
    </div>
    <div class="section-divider reveal"></div>

    <?php if (!empty($convenios)): ?>
    <div class="conv-grid">
      <?php foreach ($convenios as $conv): ?>
      <div class="conv-card reveal">
        <?php
          $logoPath = __DIR__ . '/' . $conv['logo'];
          if (file_exists($logoPath) && is_file($logoPath)):
        ?>
        <img
          src="<?= esc($conv['logo']) ?>"
          alt="Logo <?= esc($conv['nome']) ?>"
          loading="lazy"
        >
        <?php else: ?>
        <i class="fa-solid fa-shield-heart" style="font-size:2rem;color:var(--primary);" aria-hidden="true"></i>
        <?php endif; ?>
        <span class="conv-name"><?= esc($conv['nome']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center" style="color:var(--gray-text);margin-top:24px;">
      Nenhum convênio cadastrado no momento.
    </p>
    <?php endif; ?>

    <p class="text-center mt-4 reveal" style="color:var(--gray-text);font-size:.9rem;">
      Não encontrou seu plano? <a href="#contato" style="color:var(--primary);font-weight:600;">Entre em contato</a> para verificar.
    </p>
  </div>
</section>

<!-- ============================================================
     DEPOIMENTOS
     ============================================================ -->
<section id="depoimentos" aria-label="Depoimentos de pacientes">
  <div class="container">
    <div class="section-title reveal">
      <h2>O que nossos <span style="color:#7FD4FF;">Pacientes Dizem</span></h2>
      <p>A satisfação de quem confiou em nós é nossa maior recompensa.</p>
    </div>
    <div class="section-divider reveal"></div>

    <?php if (!empty($depoimentos)): ?>
    <div class="dep-grid">
      <?php foreach ($depoimentos as $dep): ?>
      <div class="dep-card reveal">
        <div class="dep-stars" aria-label="Avaliação: <?= (int)$dep['avaliacao'] ?> de 5 estrelas">
          <?= stars((int)$dep['avaliacao']) ?>
        </div>
        <p>"<?= esc($dep['texto']) ?>"</p>
        <div class="dep-author">
          <div class="dep-avatar" aria-hidden="true"><?= esc(initials($dep['nome'])) ?></div>
          <div class="dep-author-info">
            <strong><?= esc($dep['nome']) ?></strong>
            <span><?= esc(date('d/m/Y', strtotime($dep['data']))) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center" style="color:rgba(255,255,255,.7);margin-top:24px;">
      Nenhum depoimento cadastrado no momento.
    </p>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     FORMULÁRIO DE CONTATO / AGENDAMENTO
     ============================================================ -->
<section id="contato" aria-label="Contato e agendamento">
  <div class="container">
    <div class="section-title reveal">
      <h2>Agende sua <span>Consulta</span></h2>
      <p>Preencha o formulário e nossa equipe entrará em contato para confirmar seu agendamento.</p>
    </div>
    <div class="section-divider reveal"></div>

    <div class="contato-grid">
      <!-- Informações de contato -->
      <div class="contato-info reveal">
        <h3>Fale <span>conosco</span></h3>
        <p>Estamos disponíveis para tirar dúvidas, agendar consultas e oferecer o suporte que você precisa.</p>

        <div class="info-items">
          <div class="info-item">
            <div class="info-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <strong>Endereço</strong>
              <span><?= esc(SITE_ADDRESS) ?></span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></div>
            <div>
              <strong>Telefone</strong>
              <span><a href="tel:<?= preg_replace('/\D/', '', SITE_PHONE) ?>"><?= esc(SITE_PHONE) ?></a></span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></div>
            <div>
              <strong>WhatsApp</strong>
              <span><a href="https://wa.me/<?= esc(SITE_WHATSAPP) ?>" target="_blank" rel="noopener noreferrer">(11) 93456-7890</a></span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <strong>E-mail</strong>
              <span><a href="mailto:<?= esc(SITE_EMAIL) ?>"><?= esc(SITE_EMAIL) ?></a></span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
            <div>
              <strong>Horário de Atendimento</strong>
              <span>Seg–Sex: 7h às 20h<br>Sáb: 8h às 14h<br>Emergência: 24h</span>
            </div>
          </div>
        </div>

        <div class="social-links" aria-label="Redes sociais">
          <a href="#" class="social-link" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
            <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
          </a>
          <a href="#" class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
          </a>
          <a href="#" class="social-link" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
            <i class="fa-brands fa-youtube" aria-hidden="true"></i>
          </a>
          <a href="#" class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
            <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <!-- Formulário -->
      <div class="form-card reveal">
        <h3><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Solicitar <span>Agendamento</span></h3>

        <?php if ($formMsg): ?>
        <div class="alert alert-<?= esc($formType) ?>" role="alert" data-auto-hide="6000">
          <i class="fa-solid fa-<?= $formType === 'success' ? 'circle-check' : 'circle-exclamation' ?>" aria-hidden="true"></i>
          <span><?= $formMsg /* já sanitizado via implode de msgs internas */ ?></span>
        </div>
        <?php endif; ?>

        <form id="form-agendamento" method="POST" action="#contato" novalidate>
          <input type="hidden" name="action" value="agendar">

          <div class="form-row">
            <div class="form-group">
              <label for="nome">Nome completo <span class="req" aria-hidden="true">*</span></label>
              <input
                type="text"
                id="nome"
                name="nome"
                placeholder="Seu nome completo"
                value="<?= esc($formData['nome'] ?? '') ?>"
                required
                autocomplete="name"
                maxlength="150"
              >
              <span class="field-error" data-error="nome" role="alert"></span>
            </div>
            <div class="form-group">
              <label for="email">E-mail <span class="req" aria-hidden="true">*</span></label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="seu@email.com"
                value="<?= esc($formData['email'] ?? '') ?>"
                required
                autocomplete="email"
                maxlength="255"
              >
              <span class="field-error" data-error="email" role="alert"></span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="telefone">Telefone / WhatsApp <span class="req" aria-hidden="true">*</span></label>
              <input
                type="tel"
                id="telefone"
                name="telefone"
                placeholder="(11) 99999-9999"
                value="<?= esc($formData['telefone'] ?? '') ?>"
                required
                autocomplete="tel"
                maxlength="20"
              >
              <span class="field-error" data-error="telefone" role="alert"></span>
            </div>
            <div class="form-group">
              <label for="especialidade_id">Especialidade <span class="req" aria-hidden="true">*</span></label>
              <select id="especialidade_id" name="especialidade_id" required>
                <option value="">Selecione a especialidade</option>
                <?php foreach ($especialidades as $esp): ?>
                <option
                  value="<?= (int)$esp['id'] ?>"
                  <?= ((int)($formData['especialidade_id'] ?? 0) === (int)$esp['id']) ? 'selected' : '' ?>
                >
                  <?= esc($esp['nome']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <span class="field-error" data-error="especialidade_id" role="alert"></span>
            </div>
          </div>

          <div class="form-group">
            <label for="data_desejada">Data desejada <span class="req" aria-hidden="true">*</span></label>
            <input
              type="date"
              id="data_desejada"
              name="data_desejada"
              value="<?= esc($formData['data_desejada'] ?? '') ?>"
              required
            >
            <span class="field-error" data-error="data_desejada" role="alert"></span>
          </div>

          <div class="form-group">
            <label for="mensagem">Mensagem / Observações</label>
            <textarea
              id="mensagem"
              name="mensagem"
              placeholder="Descreva brevemente o motivo da consulta ou outras informações relevantes..."
              maxlength="1000"
            ><?= esc($formData['mensagem'] ?? '') ?></textarea>
            <span class="field-error" data-error="mensagem" role="alert"></span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg form-submit">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            Enviar Solicitação
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     MAPA
     ============================================================ -->
<section id="mapa" aria-label="Localização do hospital">
  <iframe
    src="<?= esc(GOOGLE_MAPS_URL) ?>"
    title="Localização do Hospital Santo Expedito - APAS"
    allowfullscreen
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
  ></iframe>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer id="footer" role="contentinfo" aria-label="Rodapé">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-brand">
        <img src="images/hse.png" alt="Logo Hospital Santo Expedito - APAS" class="footer-logo-img">
        <strong><?= esc(SITE_NAME) ?></strong>
        <span style="font-size:.8rem;color:rgba(255,255,255,.5);">CNPJ: 12.345.678/0001-99</span>
        <p>
          Comprometidos com a saúde, o bem-estar e a qualidade de vida de nossos pacientes
          há mais de 25 anos.
        </p>
        <div class="footer-social" aria-label="Redes sociais">
          <a href="#" aria-label="Facebook"  target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="YouTube"   target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-youtube"></i></a>
          <a href="#" aria-label="LinkedIn"  target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>
      </div>

      <!-- Links -->
      <div class="footer-col">
        <h4>Navegação</h4>
        <ul>
          <li><a href="#sobre"><i class="fa-solid fa-chevron-right"></i> Sobre nós</a></li>
          <li><a href="#especialidades"><i class="fa-solid fa-chevron-right"></i> Especialidades</a></li>
          <li><a href="#equipe"><i class="fa-solid fa-chevron-right"></i> Equipe médica</a></li>
          <li><a href="#convenios"><i class="fa-solid fa-chevron-right"></i> Convênios</a></li>
          <li><a href="#depoimentos"><i class="fa-solid fa-chevron-right"></i> Depoimentos</a></li>
          <li><a href="#contato"><i class="fa-solid fa-chevron-right"></i> Contato</a></li>
        </ul>
      </div>

      <!-- Especialidades -->
      <div class="footer-col">
        <h4>Especialidades</h4>
        <ul>
          <?php foreach (array_slice($especialidades, 0, 6) as $esp): ?>
          <li><a href="#especialidades"><i class="fa-solid fa-chevron-right"></i> <?= esc($esp['nome']) ?></a></li>
          <?php endforeach; ?>
          <?php if (count($especialidades) > 6): ?>
          <li><a href="#especialidades"><i class="fa-solid fa-chevron-right"></i> Ver todas</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Contato -->
      <div class="footer-col footer-contact">
        <h4>Contato</h4>
        <ul>
          <li>
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <?= esc(SITE_ADDRESS) ?>
          </li>
          <li>
            <i class="fa-solid fa-phone" aria-hidden="true"></i>
            <a href="tel:<?= preg_replace('/\D/', '', SITE_PHONE) ?>"><?= esc(SITE_PHONE) ?></a>
          </li>
          <li>
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
            <a href="https://wa.me/<?= esc(SITE_WHATSAPP) ?>" target="_blank" rel="noopener noreferrer">(13) 97404-0563</a>
          </li>
          <li>
            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
            <a href="mailto:<?= esc(SITE_EMAIL) ?>"><?= esc(SITE_EMAIL) ?></a>
          </li>
          <li>
            <i class="fa-solid fa-clock" aria-hidden="true"></i>
            Seg–Sex: 8h–17h | Administrativo<br>
            <span style="padding-left:20px;">Emergência: 24h</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= esc(SITE_NAME) ?>. Todos os direitos reservados.</p>
      <p>
        <a href="#">Política de Privacidade</a>
        &nbsp;|&nbsp;
        <a href="#">Termos de Uso</a>
      </p>
    </div>
  </div>
</footer>

<!-- WhatsApp flutuante -->
<a
  href="https://wa.me/<?= esc(SITE_WHATSAPP) ?>?text=Olá!%20Gostaria%20de%20agendar%20uma%20consulta."
  class="whatsapp-float"
  target="_blank"
  rel="noopener noreferrer"
  aria-label="Fale conosco pelo WhatsApp"
>
  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
</a>

<!-- Botão scroll to top -->
<button id="scrollTop" aria-label="Voltar ao topo" title="Voltar ao topo">
  <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
</button>

<!-- JavaScript -->
<script src="assets/js/script.js"></script>
</body>
</html>
