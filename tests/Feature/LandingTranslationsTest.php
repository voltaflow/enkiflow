<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingTranslationsTest extends TestCase
{
    /**
     * Test that the home page renders with English translations by default.
     */
    public function test_home_page_renders_with_english_translations()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Simplify your time and project management');
        $response->assertSee('Get started for free');
    }

    /**
     * Test that the home page renders with Spanish translations when using the es locale.
     */
    public function test_home_page_renders_with_spanish_translations()
    {
        $response = $this->get('/es');

        $response->assertStatus(200);
        $response->assertSee('Simplifica tu gestión de tiempo y proyectos');
        $response->assertSee('Comenzar gratis');
    }

    /**
     * Test that the locale can be switched.
     */
    public function test_locale_can_be_switched()
    {
        // Set locale to English
        $response = $this->get('/set-locale/en');
        $response->assertRedirect();

        // Check that the home page is in English
        $response = $this->get('/');
        $response->assertSee('Simplify your time and project management');

        // Set locale to Spanish
        $response = $this->get('/set-locale/es');
        $response->assertRedirect();

        // Check that the home page is in Spanish
        $response = $this->get('/');
        $response->assertSee('Simplifica tu gestión de tiempo y proyectos');
    }

    /**
     * Test that the features page renders with the correct translations.
     */
    public function test_features_page_renders_with_correct_translations()
    {
        // English
        $response = $this->get('/features');
        $response->assertStatus(200);
        $response->assertSee('EnkiFlow Features');

        // Spanish
        $response = $this->get('/es/features');
        $response->assertStatus(200);
        $response->assertSee('Características de EnkiFlow');
    }

    /**
     * Test that the pricing page renders with the correct translations.
     */
    public function test_pricing_page_renders_with_correct_translations()
    {
        // English
        $response = $this->get('/pricing');
        $response->assertStatus(200);
        $response->assertSee('Simple and transparent plans');

        // Spanish
        $response = $this->get('/es/pricing');
        $response->assertStatus(200);
        $response->assertSee('Planes simples y transparentes');
    }

    /**
     * Test that the about page renders with the correct translations.
     */
    public function test_about_page_renders_with_correct_translations()
    {
        // English
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('About EnkiFlow');

        // Spanish
        $response = $this->get('/es/about');
        $response->assertStatus(200);
        $response->assertSee('Sobre EnkiFlow');
    }

    /**
     * Test that the contact page renders with the correct translations.
     */
    public function test_contact_page_renders_with_correct_translations()
    {
        // English
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Contact us');

        // Spanish
        $response = $this->get('/es/contact');
        $response->assertStatus(200);
        $response->assertSee('Contacta con nosotros');
    }
}
