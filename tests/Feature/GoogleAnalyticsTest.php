<?php

it('does not load Google Analytics when no measurement ID is set', function () {
    config(['services.google_analytics.measurement_id' => null]);

    $this->get('/')->assertDontSee('googletagmanager.com', false);
});

it('loads Google Analytics with the configured measurement ID', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST12345']);

    $this->get('/')
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST12345', false)
        ->assertSee("gtag('config', 'G-TEST12345')", false);
});
