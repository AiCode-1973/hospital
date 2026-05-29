/**
 * script.js - Hospital Santo Expedito - APAS
 * JavaScript vanilla: animações, navbar, validação client-side
 */

'use strict';

/* ============================================================
   Modal de Aviso
   ============================================================ */
(function initModal() {
  const overlay = document.getElementById('modalAviso');
  if (!overlay) return;

  const btnAbrir  = document.getElementById('btnAbrirAviso');
  const btnFechar = document.getElementById('modalAvisoFechar');

  var timerAutoAbrir = null;

  function abrir() {
    overlay.classList.remove('modal-hidden');
    document.body.style.overflow = 'hidden';
  }

  function fechar() {
    // Cancela auto-abertura se o usuário fechar antes do timer
    if (timerAutoAbrir) { clearTimeout(timerAutoAbrir); timerAutoAbrir = null; }
    overlay.classList.add('modal-hidden');
    document.body.style.overflow = '';
    // guarda na sessão para não reabrir automaticamente
    try { sessionStorage.setItem('aviso_visto', '1'); } catch(e) {}
  }

  // Botão fechar
  if (btnFechar) btnFechar.addEventListener('click', fechar);

  // Botão no hero
  if (btnAbrir) btnAbrir.addEventListener('click', abrir);

  // Fechar ao clicar no overlay (fora da caixa)
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) fechar();
  });

  // Fechar com ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !overlay.classList.contains('modal-hidden')) fechar();
  });

  // Auto-abrir se configurado e não foi visto nesta sessão
  var autoAbrir = overlay.dataset.auto === '1';
  var jaVisto   = false;
  try { jaVisto = sessionStorage.getItem('aviso_visto') === '1'; } catch(e) {}

  if (autoAbrir && !jaVisto) {
    timerAutoAbrir = setTimeout(abrir, 800);
  }
})();

/* ============================================================
   Navbar: comportamento ao scroll + menu mobile
   ============================================================ */
(function initNavbar() {
  const navbar    = document.getElementById('navbar');
  const toggle    = document.querySelector('.nav-toggle');
  const menu      = document.querySelector('.nav-menu');
  const navLinks  = document.querySelectorAll('.nav-menu a[href^="#"]');

  // Scroll: adiciona classe .scrolled
  function onScroll() {
    navbar.classList.toggle('scrolled', window.scrollY > 60);

    // Scroll to top button
    const btn = document.getElementById('scrollTop');
    if (btn) btn.classList.toggle('visible', window.scrollY > 300);

    // Active nav link
    updateActiveLink();
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // estado inicial

  // Hamburger menu
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      const open = toggle.classList.toggle('open');
      menu.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open);
    });
  }

  // Fechar menu ao clicar em link
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      toggle?.classList.remove('open');
      menu?.classList.remove('open');
      toggle?.setAttribute('aria-expanded', 'false');
    });
  });

  // Scroll suave ao clicar em link âncora
  navLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      const target   = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        const offset = navbar.offsetHeight + 8;
        const top    = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  // Também para links fora do menu (hero CTA etc.)
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    if (!link.closest('.nav-menu')) {
      link.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        const target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          const offset = navbar.offsetHeight + 8;
          const top    = target.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top, behavior: 'smooth' });
        }
      });
    }
  });

  function updateActiveLink() {
    const sections = document.querySelectorAll('section[id]');
    let current = '';
    sections.forEach(section => {
      if (window.scrollY >= section.offsetTop - navbar.offsetHeight - 20) {
        current = '#' + section.id;
      }
    });
    navLinks.forEach(link => {
      link.classList.toggle('active', link.getAttribute('href') === current);
    });
  }
})();

/* ============================================================
   Scroll to Top
   ============================================================ */
(function initScrollTop() {
  const btn = document.getElementById('scrollTop');
  if (!btn) return;
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();

/* ============================================================
   Reveal on scroll (Intersection Observer)
   ============================================================ */
(function initReveal() {
  if (!('IntersectionObserver' in window)) {
    // Fallback: mostrar tudo imediatamente
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
})();

/* ============================================================
   Counter animado (hero stats)
   ============================================================ */
(function initCounters() {
  const counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const target = parseInt(el.dataset.count, 10);
      const suffix = el.dataset.suffix || '';
      let current  = 0;
      const step   = Math.ceil(target / 60);
      const timer  = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString('pt-BR') + suffix;
        if (current >= target) clearInterval(timer);
      }, 25);
      observer.unobserve(el);
    });
  }, { threshold: 0.5 });

  counters.forEach(el => observer.observe(el));
})();

/* ============================================================
   Validação client-side do formulário de agendamento
   ============================================================ */
(function initForm() {
  const form = document.getElementById('form-agendamento');
  if (!form) return;

  const rules = {
    nome:           { required: true, minLen: 3, label: 'Nome' },
    email:          { required: true, email: true, label: 'E-mail' },
    telefone:       { required: true, phone: true, label: 'Telefone' },
    especialidade_id: { required: true, label: 'Especialidade' },
    data_desejada:  { required: true, futureDate: true, label: 'Data desejada' },
    mensagem:       { required: false, label: 'Mensagem' },
  };

  // Máscara simples de telefone
  const telInput = form.querySelector('[name="telefone"]');
  if (telInput) {
    telInput.addEventListener('input', function () {
      let v = this.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 10) {
        v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
      } else {
        v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3').replace(/-$/, '');
      }
      this.value = v;
    });
  }

  function validateField(name, value) {
    const rule = rules[name];
    if (!rule) return null;

    if (rule.required && !value.trim()) {
      return `${rule.label} é obrigatório.`;
    }
    if (value.trim() && rule.minLen && value.trim().length < rule.minLen) {
      return `${rule.label} deve ter pelo menos ${rule.minLen} caracteres.`;
    }
    if (value.trim() && rule.email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(value.trim())) return 'Informe um e-mail válido.';
    }
    if (value.trim() && rule.phone) {
      const digits = value.replace(/\D/g, '');
      if (digits.length < 10) return 'Informe um telefone válido com DDD.';
    }
    if (value && rule.futureDate) {
      const sel   = new Date(value + 'T00:00:00');
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (sel < today) return 'A data deve ser hoje ou futura.';
    }
    return null;
  }

  function showError(name, msg) {
    const input  = form.querySelector(`[name="${name}"]`);
    const errEl  = form.querySelector(`[data-error="${name}"]`);
    if (!input || !errEl) return;
    input.classList.add('error');
    errEl.textContent = msg;
    errEl.style.display = 'block';
  }

  function clearError(name) {
    const input  = form.querySelector(`[name="${name}"]`);
    const errEl  = form.querySelector(`[data-error="${name}"]`);
    if (!input || !errEl) return;
    input.classList.remove('error');
    errEl.style.display = 'none';
  }

  // Validação em tempo real
  Object.keys(rules).forEach(name => {
    const input = form.querySelector(`[name="${name}"]`);
    if (!input) return;
    input.addEventListener('blur', () => {
      const err = validateField(name, input.value);
      if (err) showError(name, err);
      else     clearError(name);
    });
    input.addEventListener('input', () => clearError(name));
  });

  form.addEventListener('submit', function (e) {
    let valid = true;
    Object.keys(rules).forEach(name => {
      const input = form.querySelector(`[name="${name}"]`);
      if (!input) return;
      const err = validateField(name, input.value);
      if (err) {
        showError(name, err);
        valid = false;
      } else {
        clearError(name);
      }
    });
    if (!valid) {
      e.preventDefault();
      // Foca no primeiro campo com erro
      const firstErr = form.querySelector('.error');
      if (firstErr) firstErr.focus();
    }
  });
})();

/* ============================================================
   Auto-hide de alertas de feedback
   ============================================================ */
(function initAlerts() {
  const alerts = document.querySelectorAll('.alert[data-auto-hide]');
  alerts.forEach(alert => {
    const delay = parseInt(alert.dataset.autoHide, 10) || 5000;
    setTimeout(() => {
      alert.style.transition = 'opacity .5s ease';
      alert.style.opacity    = '0';
      setTimeout(() => alert.remove(), 500);
    }, delay);
  });
})();

/* ============================================================
   Definir data mínima do campo data_desejada (hoje)
   ============================================================ */
(function setMinDate() {
  const dateInput = document.querySelector('[name="data_desejada"]');
  if (!dateInput) return;
  const today = new Date().toISOString().split('T')[0];
  dateInput.setAttribute('min', today);
})();
