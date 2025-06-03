document.addEventListener('DOMContentLoaded', function() {
  // Toggle sidebar on mobile
  const navbarToggler = document.querySelector('.navbar-toggler');
  const sidebar = document.querySelector('.sidebar');
  
  if (navbarToggler && sidebar) {
    navbarToggler.addEventListener('click', function() {
      sidebar.classList.toggle('show');
    });
  }

  // Animate stats cards
  const animateStats = () => {
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
      const target = parseInt(stat.textContent);
      const increment = target / 20;
      let current = 0;
      
      const timer = setInterval(() => {
        current += increment;
        stat.textContent = Math.floor(current);
        
        if (current >= target) {
          stat.textContent = target;
          clearInterval(timer);
        }
      }, 50);
    });
  };

  // Check if stats are in viewport
  const isInViewport = (element) => {
    const rect = element.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  };

  // Run animation when stats come into view
  const statsSection = document.querySelector('.row.mb-4');
  if (statsSection) {
    window.addEventListener('scroll', function() {
      if (isInViewport(statsSection) && !statsSection.classList.contains('animated')) {
        statsSection.classList.add('animated');
        animateStats();
      }
    });

    // Initialize animation if stats are already in view
    if (isInViewport(statsSection)) {
      statsSection.classList.add('animated');
      animateStats();
    }
  }

  // Product card animations
  const animateCards = () => {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
      const cardPosition = card.getBoundingClientRect().top;
      const screenPosition = window.innerHeight / 1.2;
      
      if (cardPosition < screenPosition) {
        card.style.transitionDelay = `${index * 0.1}s`;
        card.classList.add('animate__animated', 'animate__fadeInUp');
      }
    });
  };

  // Initial check
  animateCards();
  
  // Check on scroll
  window.addEventListener('scroll', animateCards);
});