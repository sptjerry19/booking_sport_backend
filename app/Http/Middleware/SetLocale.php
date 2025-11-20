<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class SetLocale
{
    /**
     * Supported locale codes.
     *
     * @var array<int, string>
     */
    protected array $supportedLocales = ['en', 'vi'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $fallback = config('app.fallback_locale', 'en');

        $candidates = [
            $request->route('locale'),
            $request->query('lang'),
            $request->header('X-Locale'),
            $this->preferredFromHeader($request),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $candidate = strtolower(trim($candidate));

                if ($this->isSupported($candidate)) {
                    return $candidate;
                }

                $candidate = Str::substr($candidate, 0, 2);

                if ($this->isSupported($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->isSupported(App::currentLocale()) ? App::currentLocale() : $fallback;
    }

    protected function preferredFromHeader(Request $request): ?string
    {
        return $request->getPreferredLanguage($this->supportedLocales);
    }

    protected function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->supportedLocales, true);
    }
}
