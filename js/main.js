/* =========================================================
   PORTFOLIO SCRIPT v2
   - Theme toggle (dark/light) + localStorage persistence
   - Mobile nav toggle
   - Active nav-link highlighting on scroll
   - Scroll-reveal for all sections
   - Navbar scroll shadow
   - Hero typing animation
   - Hero particle canvas
   - Animated stat counters
   - Project filtering with stagger animation
   - Back-to-top button
   - Contact form validation + async submit
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {

  /* -------------------------------------------------------
     THEME TOGGLE
     Persists preference in localStorage. Default is dark.
  ------------------------------------------------------- */
  const themeToggle = document.getElementById('themeToggle');
  const html = document.documentElement;

  const serverTheme = html.getAttribute('data-theme') || 'dark';
  const savedTheme = localStorage.getItem('portfolio-theme') || serverTheme;
  html.setAttribute('data-theme', savedTheme);
  updateToggleIcon(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = html.getAttribute('data-theme') || 'dark';
      const next = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('portfolio-theme', next);
      updateToggleIcon(next);
    });
  }

  function updateToggleIcon(theme) {
    if (!themeToggle) return;
    if (theme === 'light') {
      themeToggle.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>`;
      themeToggle.setAttribute('aria-label', 'Switch to dark mode');
    } else {
      themeToggle.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"/>
          <line x1="12" y1="1" x2="12" y2="3"/>
          <line x1="12" y1="21" x2="12" y2="23"/>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
          <line x1="1" y1="12" x2="3" y2="12"/>
          <line x1="21" y1="12" x2="23" y2="12"/>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>`;
      themeToggle.setAttribute('aria-label', 'Switch to light mode');
    }
  }

  /* -------------------------------------------------------
     MOBILE NAV TOGGLE
  ------------------------------------------------------- */
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('open');
      navToggle.classList.toggle('open', isOpen);
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* -------------------------------------------------------
     ACTIVE NAV ON SCROLL
  ------------------------------------------------------- */
  const sections   = document.querySelectorAll('section[id]');
  const navAnchors = document.querySelectorAll('.nav-links a');

  const highlightNav = () => {
    let currentId = '';
    const scrollPos = window.scrollY + 140;
    sections.forEach(section => {
      if (scrollPos >= section.offsetTop) currentId = section.id;
    });
    navAnchors.forEach(anchor => {
      anchor.classList.toggle('active', anchor.getAttribute('href') === `#${currentId}`);
    });
  };
  window.addEventListener('scroll', highlightNav, { passive: true });
  highlightNav();

  /* -------------------------------------------------------
     NAVBAR SCROLL SHADOW
  ------------------------------------------------------- */
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    const updateNavbar = () => navbar.classList.toggle('is-scrolled', window.scrollY > 12);
    window.addEventListener('scroll', updateNavbar, { passive: true });
    updateNavbar();
  }

  /* -------------------------------------------------------
     SCROLL REVEAL
  ------------------------------------------------------- */
  const revealTargets = document.querySelectorAll(
    '.timeline-entry, .project-card, .reveal-up, .skills-group'
  );

  if ('IntersectionObserver' in window) {
    revealTargets.forEach(t => t.classList.add('reveal-init'));

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    revealTargets.forEach(t => observer.observe(t));

    // Failsafe
    setTimeout(() => revealTargets.forEach(t => t.classList.add('in-view')), 2500);
  }

  /* -------------------------------------------------------
     HERO TYPING ANIMATION
  ------------------------------------------------------- */
  const typingEl = document.getElementById('typingText');
  if (typingEl) {
    const roles = [
      'Web Developer',
      'Full-Stack Engineer',
      'Statistics Student',
      'Problem Solver',
      'UI Enthusiast'
    ];
    let roleIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    const TYPING_SPEED   = 80;
    const DELETING_SPEED = 45;
    const PAUSE_AFTER    = 2000;
    const PAUSE_BEFORE   = 400;

    function type() {
      const current = roles[roleIndex];

      if (isDeleting) {
        typingEl.textContent = current.substring(0, charIndex - 1);
        charIndex--;
      } else {
        typingEl.textContent = current.substring(0, charIndex + 1);
        charIndex++;
      }

      let delay = isDeleting ? DELETING_SPEED : TYPING_SPEED;

      if (!isDeleting && charIndex === current.length) {
        delay = PAUSE_AFTER;
        isDeleting = true;
      } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        roleIndex = (roleIndex + 1) % roles.length;
        delay = PAUSE_BEFORE;
      }
      setTimeout(type, delay);
    }
    setTimeout(type, 800);
  }

  /* -------------------------------------------------------
     HERO PARTICLE CANVAS
  ------------------------------------------------------- */
  const canvas = document.getElementById('particleCanvas');
  if (canvas) {
    const ctx = canvas.getContext('2d');
    let particles = [];
    let animFrameId;

    const resize = () => {
      canvas.width  = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;
    };
    resize();
    window.addEventListener('resize', resize, { passive: true });

    class Particle {
      constructor() { this.reset(); }
      reset() {
        this.x  = Math.random() * canvas.width;
        this.y  = Math.random() * canvas.height;
        this.r  = Math.random() * 1.5 + 0.4;
        this.vx = (Math.random() - 0.5) * 0.35;
        this.vy = (Math.random() - 0.5) * 0.35;
        this.alpha = Math.random() * 0.45 + 0.1;
        const colors = ['79,142,247', '124,90,245', '245,166,35'];
        this.color = colors[Math.floor(Math.random() * colors.length)];
      }
      update() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height) {
          this.reset();
        }
      }
      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${this.color},${this.alpha})`;
        ctx.fill();
      }
    }

    const COUNT = Math.min(90, Math.floor(canvas.width * canvas.height / 10000));
    for (let i = 0; i < COUNT; i++) particles.push(new Particle());

    // Draw connection lines between nearby particles
    function drawConnections() {
      const MAX_DIST = 120;
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < MAX_DIST) {
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.strokeStyle = `rgba(79,142,247,${0.12 * (1 - dist / MAX_DIST)})`;
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }
      }
    }

    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawConnections();
      particles.forEach(p => { p.update(); p.draw(); });
      animFrameId = requestAnimationFrame(animate);
    }
    animate();

    // Pause when hero is not visible (perf)
    if ('IntersectionObserver' in window) {
      const heroObs = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          animate();
        } else {
          cancelAnimationFrame(animFrameId);
        }
      });
      const hero = document.querySelector('.hero');
      if (hero) heroObs.observe(hero);
    }
  }

  /* -------------------------------------------------------
     ANIMATED STAT COUNTERS
  ------------------------------------------------------- */
  const statNumbers = document.querySelectorAll('.stat-number[data-target]');
  if (statNumbers.length && 'IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el     = entry.target;
        const target = parseInt(el.dataset.target, 10);
        const suffix = el.dataset.suffix || '';
        const duration = 1500;
        const step = target / (duration / 16);
        let current = 0;
        const update = () => {
          current = Math.min(current + step, target);
          el.textContent = Math.round(current) + suffix;
          if (current < target) requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
        counterObserver.unobserve(el);
      });
    }, { threshold: 0.5 });
    statNumbers.forEach(el => counterObserver.observe(el));
  }

  /* -------------------------------------------------------
     PROJECT FILTERING (with stagger)
  ------------------------------------------------------- */
  const filterBar    = document.getElementById('filterBar');
  const projectCards = document.querySelectorAll('.project-card');

  if (filterBar) {
    filterBar.addEventListener('click', (e) => {
      const btn = e.target.closest('.filter-btn');
      if (!btn) return;
      filterBar.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.filter;
      let visibleIndex = 0;

      projectCards.forEach(card => {
        const tags = (card.dataset.tags || '').split(',');
        const show = filter === 'all' || tags.includes(filter);

        if (show) {
          card.classList.remove('hidden');
          card.style.transitionDelay = `${visibleIndex * 60}ms`;
          visibleIndex++;
        } else {
          card.classList.add('hidden');
          card.style.transitionDelay = '0ms';
        }
      });
    });
  }

  /* -------------------------------------------------------
     BACK TO TOP
  ------------------------------------------------------- */
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    window.addEventListener('scroll', () => {
      backToTop.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* -------------------------------------------------------
     CONTACT FORM
  ------------------------------------------------------- */
  const form      = document.getElementById('contactForm');
  const status    = document.getElementById('formStatus');
  const submitBtn = document.getElementById('submitBtn');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      status.textContent = '';
      status.className = 'form-status';

      const name    = form.name.value.trim();
      const email   = form.email.value.trim();
      const message = form.message.value.trim();
      const website = form.website ? form.website.value.trim() : '';

      if (!name || !email || !message) {
        status.textContent = 'Please fill in all fields.';
        status.classList.add('error');
        return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        status.textContent = 'Please enter a valid email address.';
        status.classList.add('error');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending…';

      try {
        const response = await fetch('php/contact.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, message, website })
        });
        const data = await response.json();
        if (data.success) {
          status.textContent = data.message || 'Message sent — thanks for reaching out!';
          status.classList.add('success');
          form.reset();
        } else {
          status.textContent = data.message || 'Something went wrong. Please try again.';
          status.classList.add('error');
        }
      } catch {
        status.textContent = 'Could not reach the server. Please try again later.';
        status.classList.add('error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
      }
    });
  }

});
