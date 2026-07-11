/* =========================================================
   PORTFOLIO SCRIPT
   - Mobile nav toggle
   - Active nav-link highlighting on scroll
   - Scroll-reveal for section content (JS-independent - visible by
     default, animation is a progressive enhancement only)
   - Navbar scroll shadow
   - Project filtering by tag
   - Back-to-top button
   - Contact form validation + async submit to php/contact.php
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Mobile nav toggle ---------- */
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  // Close mobile nav after clicking a link
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      navToggle.classList.remove('open');
      navToggle.setAttribute('aria-expanded', 'false');
    });
  });

  /* ---------- Active nav link on scroll ---------- */
  const sections = document.querySelectorAll('section[id]');
  const navAnchors = document.querySelectorAll('.nav-links a');

  const highlightNav = () => {
    let currentId = '';
    const scrollPos = window.scrollY + 120;

    sections.forEach(section => {
      if (scrollPos >= section.offsetTop) {
        currentId = section.id;
      }
    });

    navAnchors.forEach(anchor => {
      anchor.classList.toggle('active', anchor.getAttribute('href') === `#${currentId}`);
    });
  };

  window.addEventListener('scroll', highlightNav);
  highlightNav();

  /* ---------- Scroll-reveal (section content) ----------
     Content is fully visible by default in CSS (no JS required to see it).
     Only if IntersectionObserver is available do we add the 'reveal-init'
     class to fade elements out, then fade them back in as they scroll into
     view. If this script fails to load or run for any reason, visitors
     still see all content immediately - the animation is a bonus, not a
     requirement. */
  const revealTargets = document.querySelectorAll(
    '.timeline-entry, .project-card, .reveal-up, .skills-group'
  );

  if ('IntersectionObserver' in window) {
    revealTargets.forEach(target => target.classList.add('reveal-init'));

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    revealTargets.forEach(target => observer.observe(target));

    // Failsafe: if for any reason the observer doesn't fire (some older
    // browsers/edge cases), force everything visible after a short delay
    // so content can never get stuck invisible.
    setTimeout(() => {
      revealTargets.forEach(target => target.classList.add('in-view'));
    }, 2000);
  }
  // No 'else' needed - targets are already visible by default via CSS.

  /* ---------- Navbar scroll shadow ----------
     Purely cosmetic - if this never runs, the navbar just keeps its
     default resting appearance, nothing breaks. */
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    const updateNavbarShadow = () => {
      navbar.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    window.addEventListener('scroll', updateNavbarShadow);
    updateNavbarShadow();
  }

  /* ---------- Project filtering ---------- */
  const filterBar = document.getElementById('filterBar');
  const projectCards = document.querySelectorAll('.project-card');

  if (filterBar) {
    filterBar.addEventListener('click', (e) => {
      const btn = e.target.closest('.filter-btn');
      if (!btn) return;

      filterBar.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.filter;

      projectCards.forEach(card => {
        const tags = (card.dataset.tags || '').split(',');
        const show = filter === 'all' || tags.includes(filter);
        card.classList.toggle('hidden', !show);
      });
    });
  }

  /* ---------- Back to top button ---------- */
  const backToTop = document.getElementById('backToTop');

  window.addEventListener('scroll', () => {
    backToTop.classList.toggle('visible', window.scrollY > 500);
  });

  backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---------- Contact form ---------- */
  const form = document.getElementById('contactForm');
  const status = document.getElementById('formStatus');
  const submitBtn = document.getElementById('submitBtn');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      status.textContent = '';
      status.className = 'form-status';

      const name = form.name.value.trim();
      const email = form.email.value.trim();
      const message = form.message.value.trim();
      const website = form.website.value.trim(); // honeypot

      // Basic client-side validation (server re-validates everything)
      if (!name || !email || !message) {
        status.textContent = 'Please fill in all fields.';
        status.classList.add('error');
        return;
      }

      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(email)) {
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
      } catch (err) {
        status.textContent = 'Could not reach the server. Please try again later.';
        status.classList.add('error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
      }
    });
  }

});
