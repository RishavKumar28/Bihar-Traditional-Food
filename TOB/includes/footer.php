    </main>

    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 40px 0; margin-top: auto;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 30px;">
                <div>
                    <h3 style="color: #e74c3c; margin-bottom: 20px; font-size: 1.5rem;">Bihar Traditional Food</h3>
                    <p style="color: #bdc3c7; line-height: 1.6;">Experience authentic Bihari cuisine with traditional recipes passed down through generations.</p>
                    <div style="margin-top: 20px; display: flex; gap: 15px;">
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com/_useless_dork_/" style="color: white; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="<?php echo BASE_URL; ?>index.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-home fa-sm"></i> Home
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="<?php echo BASE_URL; ?>user/menu.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-utensils fa-sm"></i> Menu
                            </a>
                        </li>
                        <?php if($auth->isLoggedIn()): ?>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo BASE_URL; ?>user/cart.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-shopping-cart fa-sm"></i> Cart
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo BASE_URL; ?>user/orders.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-shopping-bag fa-sm"></i> My Orders
                                </a>
                            </li>
                        <?php else: ?>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo BASE_URL; ?>login.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-sign-in-alt fa-sm"></i> Login
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo BASE_URL; ?>register.php" style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-plus fa-sm"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Contact Us</h4>
                    <div style="color: #bdc3c7;">
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-map-marker-alt"></i> Patna, Bihar, India
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-phone"></i> +91 6202513801
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-envelope"></i> info@biharfood.com
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-clock"></i> 10:00 AM - 11:00 PM
                        </p>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid #34495e; padding-top: 20px; text-align: center; color: #95a5a6;">
                <p>&copy; <?php echo date('Y'); ?> Bihar Traditional Food. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 0.9rem;">
                    Made with <i class="fas fa-heart" style="color: #e74c3c;"></i> in Bihar
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('navMenu').classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navMenu = document.getElementById('navMenu');
            const mobileToggle = document.getElementById('mobileToggle');
            
            if (!navMenu.contains(event.target) && !mobileToggle.contains(event.target)) {
                navMenu.classList.remove('active');
                mobileToggle.querySelector('i').classList.remove('fa-times');
                mobileToggle.querySelector('i').classList.add('fa-bars');
            }
        });

        // Handle mobile dropdown
        if (window.innerWidth <= 768) {
            const dropdownToggle = document.querySelector('#userDropdown > a');
            if (dropdownToggle) {
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.parentElement.classList.toggle('active');
                    
                    // Close other dropdowns
                    document.querySelectorAll('.dropdown').forEach(other => {
                        if (other !== this.parentElement) {
                            other.classList.remove('active');
                        }
                    });
                });
            }
        }

        // Close dropdowns when clicking elsewhere on mobile
        document.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>