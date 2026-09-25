Estos son los requires que se AGREGAN al composer.json que genera `laravel/laravel` + Breeze.
No reemplaces el composer.json completo del proyecto nuevo; solo asegúrate de tener estos:

"require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "inertiajs/inertia-laravel": "^1.0",
    "barryvdh/laravel-dompdf": "^3.0"
},
"require-dev": {
    "laravel/breeze": "^2.0"
}
