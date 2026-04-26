document.addEventListener('DOMContentLoaded', () => {
    // Typing Effect Logic
    const typingText = document.querySelector('.typing-text');
    if (typingText) {
        const titlesString = typingText.getAttribute('data-titles');
        const titles = titlesString ? titlesString.split(',').map(t => t.trim()) : ["I'm a Designer"];
        let titleIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const typeSpeed = 100;
        const deleteSpeed = 50;
        const waitTime = 2000;

        function type() {
            const currentTitle = titles[titleIndex];
            if (isDeleting) {
                typingText.innerHTML = `${currentTitle.substring(0, charIndex)}<span class="cursor">|</span>`;
                charIndex--;
            } else {
                typingText.innerHTML = `${currentTitle.substring(0, charIndex)}<span class="cursor">|</span>`;
                charIndex++;
            }

            let nextSpeed = isDeleting ? deleteSpeed : typeSpeed;

            if (!isDeleting && charIndex > currentTitle.length) {
                isDeleting = true;
                nextSpeed = waitTime;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                titleIndex = (titleIndex + 1) % titles.length;
                nextSpeed = 500;
            }

            setTimeout(type, nextSpeed);
        }
        type();
    }

    // Side Nav Logic
    const sideNav = document.getElementById('side-nav');
    const menuToggle = document.querySelector('.menu-toggle');
    const closeNav = document.getElementById('close-nav');

    if (menuToggle && sideNav) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent document click from firing
            sideNav.classList.add('active');
        });
    }

    if (closeNav && sideNav) {
        closeNav.addEventListener('click', (e) => {
            e.stopPropagation();
            sideNav.classList.remove('active');
        });
    }

    // Close nav on click outside
    document.addEventListener('click', (e) => {
        if (sideNav && sideNav.classList.contains('active')) {
            if (!sideNav.contains(e.target) && !menuToggle.contains(e.target)) {
                sideNav.classList.remove('active');
            }
        }
    });

    // Slider Logic
    const slides = document.querySelectorAll('.slide');
    if (slides.length > 1) {
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);
    } else if (slides.length === 1) {
        slides[0].classList.add('active');
    }

    // Page Transition Logic
    // 1. Entry Transition (Based on stored direction)
    const handleEntry = () => {
        document.body.classList.remove('slide-up-out', 'slide-down-out', 'animating');
        let storedDirection = 'slide-up';
        try {
            storedDirection = localStorage.getItem('lastTransition') || 'slide-up';
        } catch (e) {
            console.error("LocalStorage access denied", e);
        }
        
        // Remove any old transition classes
        document.body.classList.remove('slide-up-in', 'slide-down-in');
        document.body.classList.add(`${storedDirection}-in`);
        
        setTimeout(() => {
            document.body.classList.add('loaded');
        }, 50);

        // Fail-safe: Force loaded class after 1 second if still not there
        setTimeout(() => {
            if (!document.body.classList.contains('loaded')) {
                document.body.classList.add('loaded');
            }
        }, 1000);
    };

    // Handle back button / cache reload
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            handleEntry();
        }
    });

    handleEntry();

    // 2. Exit Transition (Determine Direction)
    const menuOrder = ['index', 'about', 'portfolio', 'services', 'contact', 'blog', 'login'];
    let currentPage = window.location.pathname.split('/').pop() || 'index';
    currentPage = currentPage.replace('.php', ''); // Fallback for transition period
    const currentIndex = menuOrder.indexOf(currentPage);

    const links = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('javascript:') && !href.startsWith('mailto:') && !href.startsWith('tel:') && !href.includes('#')) {
                e.preventDefault();
                
                let targetPage = href.split('/').pop() || 'index';
                targetPage = targetPage.replace('.php', '');
                const targetIndex = menuOrder.indexOf(targetPage);
                
                // Determine direction: Next (up) or Back (down)
                const direction = (targetIndex >= currentIndex) ? 'slide-up' : 'slide-down';
                
                localStorage.setItem('lastTransition', direction);
                document.body.classList.remove('loaded');
                document.body.classList.add(`${direction}-out`, 'animating');
                
                setTimeout(() => {
                    window.location.href = href;
                }, 700);
            }
        });
    });
});
