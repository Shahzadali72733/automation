<?php
/**
 * Service Dispatch — Custom Footer
 * Overrides the parent theme footer completely
 */
?>
    </div><!-- .site-content -->

    <footer id="apus-footer" class="apus-footer" role="contentinfo">
        <div class="footer-inner">
            <div class="sd-footer">
                <div class="sd-footer-main">
                    <div class="container">
                        <div class="sd-footer-grid">
                            <div class="sd-footer-col">
                                <h4>Service Dispatch</h4>
                                <p>Automated job management system for commercial service coordination. From request to payment — fully automated.</p>
                            </div>
                            <div class="sd-footer-col">
                                <h4>For Clients</h4>
                                <ul>
                                    <li><a href="<?php echo esc_url(home_url('/service-request/')); ?>">Submit Service Request</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/client-dashboard/')); ?>">Client Dashboard</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/my-account/')); ?>">My Account</a></li>
                                </ul>
                            </div>
                            <div class="sd-footer-col">
                                <h4>For Vendors</h4>
                                <ul>
                                    <li><a href="<?php echo esc_url(home_url('/vendor-dashboard/')); ?>">Vendor Dashboard</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/vendor-registration/')); ?>">Become a Vendor</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/my-account/')); ?>">My Account</a></li>
                                </ul>
                            </div>
                            <div class="sd-footer-col">
                                <h4>Contact</h4>
                                <ul>
                                    <li><a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact Us</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a></li>
                                    <li><a href="<?php echo esc_url(home_url('/terms/')); ?>">Terms of Service</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sd-footer-bottom">
                    <div class="container">
                        <p>&copy; <?php echo date('Y'); ?> Service Dispatch Automation. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
        <a href="#" id="back-to-top" class="add-fix-top">
            <i class="fa fa-angle-up" aria-hidden="true"></i>
        </a>
    </footer>

    <?php get_template_part('sidebar'); ?>

</div><!-- .site -->

<?php wp_footer(); ?>
</body>
</html>
