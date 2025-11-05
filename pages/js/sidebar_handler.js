document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainArea = document.querySelector('.main-area');

    function handleSidebar() {
        const isMobile = window.innerWidth <= 768;
        
        if (sidebarToggle) {
            sidebarToggle.style.display = isMobile ? 'flex' : 'none';
        }
        
        if (!isMobile && sidebar) {
            sidebar.classList.remove('collapsed');
            if (mainArea) mainArea.classList.remove('expanded');
        }
    }

    // Initial check
    handleSidebar();

    // Handle window resize
    window.addEventListener('resize', handleSidebar);

    // Handle toggle click
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
                if (mainArea) mainArea.classList.toggle('expanded');
            }
        });
    }

    // Handle clicks outside sidebar on mobile
    document.addEventListener('click', function(e) {
        const isMobile = window.innerWidth <= 768;
        if (isMobile && sidebar && !sidebar.contains(e.target) && 
            sidebarToggle && !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('collapsed');
            if (mainArea) mainArea.classList.remove('expanded');
        }
    });
});