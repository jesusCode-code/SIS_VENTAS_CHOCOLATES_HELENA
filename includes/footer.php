</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
    // Cerrar automáticamente el sidebar en móvil al hacer click en un link
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebarOffcanvas');
        const links = sidebar.querySelectorAll('.nav-link:not(.sidebar-link)');

        links.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebar);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            });
        });
    });
</script>
</body>

</html>