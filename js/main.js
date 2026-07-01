document.addEventListener('DOMContentLoaded', () => {
  fetch('auth/status')
    .then(r => r.json())
    .then(data => {
      const navAuth = document.getElementById('nav-auth');
      if (data.authenticated && navAuth) {
        navAuth.innerHTML = `<a href="dashboard" class="nav-btn" style="background:var(--accent-2);color:var(--primary)">Painel</a>`;
      }
    })
    .catch(() => {});
  const toggle = document.getElementById('mobile-toggle');
  const navLinks = document.getElementById('nav-links');

  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
      const spans = toggle.querySelectorAll('span');
      spans.forEach(s => s.classList.toggle('active'));
    });

    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('active');
      });
    });
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.service-card, .feature-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
  });
});
