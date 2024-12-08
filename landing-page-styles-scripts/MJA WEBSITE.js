document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown menu toggle
    const menuBtn = document.querySelector('.menuBtn');
    const menuBtnIcon = document.querySelector('.menuBtn img');
    const dropDownMenu = document.querySelector('.dropdownMenu');
    const navLinks = document.querySelectorAll('.scroll-link');

    // Toggle dropdown menu
    menuBtn.onclick = function() {
        dropDownMenu.classList.toggle('open');

        if (dropDownMenu.classList.contains('open')) {
            menuBtnIcon.src = 'Images/close.png';
        } else {
            menuBtnIcon.src = 'Images/menu.png';
        }
    };

    // Smooth scrolling to sections
    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            const section = document.querySelector(this.getAttribute('href'));
            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });

                dropDownMenu.classList.remove('open');
                menuBtnIcon.src = 'Images/menu.png';
            }
        });
    });
});
