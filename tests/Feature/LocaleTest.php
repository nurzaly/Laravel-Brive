<?php

it('stores a supported locale in the session', function (string $locale) {
    $this->get(route('setlang', $locale))
        ->assertRedirect()
        ->assertSessionHas('locale', $locale);
})->with(['en', 'id']);

it('rejects an unsupported locale', function (string $locale) {
    $this->get('/setLang/' . $locale)->assertNotFound();
})->with(['xx', '..%2F..', 'en-US']);
