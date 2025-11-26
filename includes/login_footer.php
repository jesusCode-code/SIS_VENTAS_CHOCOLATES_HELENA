</div>

    <!-- Footer minimalista -->
    <div class="login-footer">
        <p>&copy; <?php echo date('Y'); ?> Chocolates Helena. Todos los derechos reservados.</p>
    </div>

    <style>
        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px;
            text-align: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(107, 68, 35, 0.1);
            z-index: 5;
        }

        .login-footer p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .login-footer {
                font-size: 0.85rem;
                padding: 12px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Animación de entrada para el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.login-card');
            if (loginCard) {
                loginCard.style.opacity = '0';
                loginCard.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    loginCard.style.transition = 'all 0.6s ease';
                    loginCard.style.opacity = '1';
                    loginCard.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>