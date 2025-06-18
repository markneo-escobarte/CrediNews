document.addEventListener('DOMContentLoaded', function() {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    var passwordInput = document.getElementById('password');
    var passwordStrength = document.getElementById('password-strength');
    
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
            var password = passwordInput.value;
            var strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
            
            switch (strength) {
                case 0:
                case 1:
                    passwordStrength.className = 'progress-bar bg-danger';
                    passwordStrength.style.width = '20%';
                    passwordStrength.textContent = 'Very Weak';
                    break;
                case 2:
                    passwordStrength.className = 'progress-bar bg-warning';
                    passwordStrength.style.width = '40%';
                    passwordStrength.textContent = 'Weak';
                    break;
                case 3:
                    passwordStrength.className = 'progress-bar bg-info';
                    passwordStrength.style.width = '60%';
                    passwordStrength.textContent = 'Medium';
                    break;
                case 4:
                    passwordStrength.className = 'progress-bar bg-primary';
                    passwordStrength.style.width = '80%';
                    passwordStrength.textContent = 'Strong';
                    break;
                case 5:
                    passwordStrength.className = 'progress-bar bg-success';
                    passwordStrength.style.width = '100%';
                    passwordStrength.textContent = 'Very Strong';
                    break;
            }
        });
    }

    const counters = document.querySelectorAll('.counter');
    const speed = 200;

    counters.forEach(counter => {
        const animate = () => {
            const value = +counter.getAttribute('data-target');
            const data = +counter.innerText;
            const time = value / speed;
            
            if (data < value) {
                counter.innerText = Math.ceil(data + time);
                setTimeout(animate, 1);
            } else {
                counter.innerText = value;
            }
        }
        
        if (!counter.getAttribute('data-target')) {
            counter.setAttribute('data-target', counter.innerText);
        }
        
        animate();
    });

    const contentTextarea = document.getElementById('content');
    const charCounter = document.getElementById('char-counter');
    
    if (contentTextarea && charCounter) {
        contentTextarea.addEventListener('input', function() {
            const remaining = 5000 - contentTextarea.value.length;
            charCounter.textContent = remaining;
            
            if (remaining < 0) {
                charCounter.classList.add('text-danger');
                charCounter.classList.remove('text-muted');
            } else {
                charCounter.classList.remove('text-danger');
                charCounter.classList.add('text-muted');
            }
        });
    }

    const togglePassword = document.querySelector('.toggle-password');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordField = document.querySelector(this.getAttribute('toggle'));
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    const datepickers = document.querySelectorAll('.datepicker');
    
    if (datepickers.length > 0 && typeof flatpickr !== 'undefined') {
        datepickers.forEach(function(el) {
            flatpickr(el, {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });
        });
    }

    const animateElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animateElements.length > 0) {
        const checkIfInView = () => {
            animateElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('animate-fade-in');
                }
            });
        };
        
        window.addEventListener('scroll', checkIfInView);
        checkIfInView();
    }
});