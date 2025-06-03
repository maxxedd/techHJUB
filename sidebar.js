// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.main-content').classList.toggle('active');
});

// Set active nav link based on current page
document.querySelectorAll('.nav-link').forEach(link => {
    if(link.href === window.location.href) {
        link.classList.add('active');
    }
});