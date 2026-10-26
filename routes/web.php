<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'dashboard')->name('dashboard');

Volt::route('/items', 'items.index')->name('items.index');
Volt::route('/items/create', 'items.form')->name('items.create');
Volt::route('/items/{item}/edit', 'items.form')->name('items.edit');

Volt::route('/recipes', 'recipes.index')->name('recipes.index');
Volt::route('/recipes/create', 'recipes.form')->name('recipes.create');
Volt::route('/recipes/{recipe}/edit', 'recipes.form')->name('recipes.edit');
Volt::route('/recipes/{recipe}', 'recipes.show')->name('recipes.show');
