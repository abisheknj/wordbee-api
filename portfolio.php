<?php
/*
Plugin Name: Simple Portfolio
Description: A simple WordPress plugin to display a portfolio using shortcode.
Version: 1.0
Author: Your Name
*/

// Add shortcode
function display_portfolio_shortcode() {
    ob_start(); ?>

    <div class="portfolio" style="max-width: 800px; margin: 0 auto; padding: 20px;">

        <h2 style="text-align: center; font-size: 36px; margin-bottom: 30px;">Portfolio</h2>

        <div class="portfolio-content" style="display: flex; flex-wrap: wrap; justify-content: space-between;">

            <div class="portfolio-item" style="flex-basis: calc(33.33% - 20px); margin-bottom: 40px;">
                <img src="YOUR_IMAGE_URL" alt="Portfolio Item 1" style="max-width: 100%; height: auto;">
                <div class="portfolio-description" style="background-color: #f9f9f9; padding: 20px; text-align: center;">
                    <h3 style="margin-top: 0;">React Project</h3>
                    <p style="margin-bottom: 0;">Hi, I'm Abishek, and I am a software developer. I have recently completed a React project. It was an exciting experience, and I'm proud of the result!</p>
                </div>
            </div>

            <!-- Add more portfolio items like this -->

        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('portfolio', 'display_portfolio_shortcode');
