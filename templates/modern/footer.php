    </main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <?php if(!empty($footer_content)): ?>
                        <?php echo $footer_content; ?>
                    <?php else: ?>
                        <p>&copy; <?php echo date('Y'); ?> <?php echo !empty($site_name) ? $site_name : 'Payment Terminal' ?>. All rights reserved.</p>
                    <?php endif; ?>
                    
                    <div class="footer-links">
                        <?php if(!empty($privacy_policy_url)): ?>
                            <a href="<?php echo $privacy_policy_url; ?>" class="footer-link">Privacy Policy</a>
                        <?php endif; ?>
                        
                        <?php if(!empty($terms_url)): ?>
                            <span class="footer-separator">•</span>
                            <a href="<?php echo $terms_url; ?>" class="footer-link">Terms of Service</a>
                        <?php endif; ?>
                        
                        <?php if(!empty($contact_url)): ?>
                            <span class="footer-separator">•</span>
                            <a href="<?php echo $contact_url; ?>" class="footer-link">Contact Us</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-section">
                    <div class="payment-methods">
                        <span class="payment-method">
                            <i class="fab fa-cc-visa fa-2x"></i>
                        </span>
                        <span class="payment-method">
                            <i class="fab fa-cc-mastercard fa-2x"></i>
                        </span>
                        <span class="payment-method">
                            <i class="fab fa-cc-amex fa-2x"></i>
                        </span>
                        <span class="payment-method">
                            <i class="fab fa-cc-discover fa-2x"></i>
                        </span>
                    </div>
                    
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>Secure Payment</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Modern JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/modern.js"></script>
    
    <style>
        .site-footer {
            background-color: white;
            padding: 2.5rem 0;
            margin-top: 3rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-light);
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .footer-section {
            flex: 1;
            min-width: 250px;
        }
        
        .footer-links {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 1rem;
        }
        
        .footer-link {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .footer-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .footer-separator {
            color: var(--border-color);
        }
        
        .payment-methods {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .payment-method {
            color: var(--text-light);
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .payment-method:hover {
            opacity: 1;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--light-bg);
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .security-badge i {
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
            }
            
            .payment-methods {
                justify-content: center;
                margin-top: 1.5rem;
            }
            
            .security-badge {
                margin: 1rem auto 0;
            }
        }
    </style>
    
    <?php if(!empty($additional_footer)): ?>
        <?php echo $additional_footer; ?>
    <?php endif; ?>
    
    <?php if(!empty($debug_output)): ?>
        <div class="debug-output">
            <?php echo $debug_output; ?>
        </div>
    <?php endif; ?>
</body>
</html>
