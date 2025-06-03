document.addEventListener('DOMContentLoaded', function() {
  // Animation for product cards on scroll
  const animateOnScroll = () => {
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
  animateOnScroll();
  
  // Check on scroll
  window.addEventListener('scroll', animateOnScroll);

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelector(this.getAttribute('href')).scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
});