document.addEventListener('DOMContentLoaded', function() {
    // Toggle passkey field based on user type
    const userTypeSelect = document.getElementById('userType');
    const passkeyGroup = document.getElementById('passkeyGroup');
    
    if (userTypeSelect && passkeyGroup) {
        userTypeSelect.addEventListener('change', function() {
            passkeyGroup.style.display = this.value === 'employee' ? 'block' : 'none';
        });
    }
    
    // Handle modal switching between login and register
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    
    if (loginModal && registerModal) {
        // When clicking "Register here" in login modal
        loginModal.addEventListener('show.bs.modal', function() {
            const registerLink = loginModal.querySelector('[data-bs-target="#registerModal"]');
            if (registerLink) {
                registerLink.addEventListener('click', function() {
                    const modal = bootstrap.Modal.getInstance(loginModal);
                    modal.hide();
                });
            }
        });
        
        // When clicking "Login here" in register modal
        registerModal.addEventListener('show.bs.modal', function() {
            const loginLink = registerModal.querySelector('[data-bs-target="#loginModal"]');
            if (loginLink) {
                loginLink.addEventListener('click', function() {
                    const modal = bootstrap.Modal.getInstance(registerModal);
                    modal.hide();
                });
            }
        });
    }
    
    // Show appropriate modal if there's an error
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login_error')) {
        const modal = new bootstrap.Modal(document.getElementById('loginModal'));
        modal.show();
    } else if (urlParams.has('register_error')) {
        const modal = new bootstrap.Modal(document.getElementById('registerModal'));
        modal.show();
    }
});